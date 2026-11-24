// ============================================================
// Vigil Drive Watcher — runs every 5 min as the folder owner.
// Reports only CHANGES to the engine, colors folders only when
// the color changes, resumes where it stopped on the next run.
// ============================================================

// ---------- Settings ----------
const ENGINE_URL = "https://your-engine-url.example.com";
const WEBHOOK_SECRET = "PASTE_CURRENT_SECRET_HERE";
const ROOT_FOLDER_ID = "your_root_act_folder_id";

const TRACKED_SUBFOLDERS = [
    "სკანი",
    "ფოტოები",
    "შიდა ნახაზები",
    "გამიჯვნები",
    "ხარჯთაღრიცხვა",
    "ტოპო ნარგავები",
];

const GRACE_MS = 5 * 60 * 1000; // folder must stay empty 5 min before "removed"
const MAX_RUNTIME_MS = 4.5 * 60 * 1000; // stop safely before Apps Script's 6-min limit

const COLOR_GREEN = "#00843D";
const COLOR_YELLOW = "#F6C026";
const COLOR_NONE = "";

// ---------- Memory (PropertiesService) ----------
// One entry per ACT folder:
// { sub: { 'ფოტოები': 'full' | 'empty' | 'grace:<ms>' | 'removed', ... },
//   colors: { '<folderId>': '#00843D', ... } }

function loadAllState() {
    return PropertiesService.getScriptProperties().getProperties();
}

function readActState(allState, actFolderId) {
    var raw = allState["act:" + actFolderId];
    return raw ? JSON.parse(raw) : { sub: {}, colors: {} };
}

function writeActState(actFolderId, state) {
    PropertiesService.getScriptProperties().setProperty(
        "act:" + actFolderId,
        JSON.stringify(state),
    );
}

// ---------- Engine webhook ----------

function sendWebhook(actNumber, subfolderName, changeType) {
    try {
        var response = UrlFetchApp.fetch(
            ENGINE_URL + "/webhook/drive?secret=" + WEBHOOK_SECRET,
            {
                method: "post",
                contentType: "application/json",
                payload: JSON.stringify({
                    act_number: actNumber,
                    subfolder: subfolderName,
                    change_type: changeType,
                }),
                muteHttpExceptions: true,
            },
        );

        var ok = response.getResponseCode() === 200;
        Logger.log(
            (ok ? "Sent " : "FAILED ") +
                changeType +
                ": ACT " +
                actNumber +
                " / " +
                subfolderName,
        );
        return ok;
    } catch (e) {
        Logger.log(
            "Webhook error: ACT " +
                actNumber +
                " / " +
                subfolderName +
                " — " +
                e,
        );
        return false;
    }
}

// ---------- One subfolder: compare last state with now ----------
// Returns the new state string. On a failed webhook it returns the
// previous state, so the same change is retried on the next run.

function checkSubfolder(actNumber, name, hasFiles, prev, now) {
    var inGrace = typeof prev === "string" && prev.indexOf("grace:") === 0;

    if (hasFiles) {
        if (prev === "full") return "full"; // unchanged
        if (inGrace) return "full"; // files came back in time — never unticked
        // unknown / empty / removed → files appeared
        return sendWebhook(actNumber, name, "added") ? "full" : prev;
    }

    // Folder is empty
    if (prev === "full") return "grace:" + now; // start the 5-min timer

    if (inGrace) {
        var since = Number(prev.split(":")[1]);
        if (now - since < GRACE_MS) return prev; // still waiting
        return sendWebhook(actNumber, name, "removed") ? "removed" : prev;
    }

    if (prev === "removed") return "removed"; // already reported
    return "empty"; // new or still-empty folder — stay quiet
}

// ---------- Colors: only call Drive when the color changes ----------

function setColorIfChanged(folderId, color, state) {
    if (state.colors[folderId] === color) return;

    Drive.Files.update({ folderColorRgb: color }, folderId);
    state.colors[folderId] = color;
}

// ---------- Signatures: ONE call per run for all ACTs ----------
// Returns { '329': true, ... } or null if the engine is unreachable.

function fetchSignatureStatuses() {
    try {
        var response = UrlFetchApp.fetch(
            ENGINE_URL + "/webhook/act-statuses?secret=" + WEBHOOK_SECRET,
            { muteHttpExceptions: true },
        );
        if (response.getResponseCode() !== 200) {
            Logger.log("act-statuses returned " + response.getResponseCode());
            return null;
        }
        return JSON.parse(response.getContentText()).statuses;
    } catch (e) {
        Logger.log("act-statuses error: " + e);
        return null;
    }
}

// ---------- Root ACT folder color ----------
// Returns a color, or null = "can't decide, keep current color".

function decideRootColor(actNumber, found, withFiles, statuses) {
    if (withFiles === 0) return COLOR_NONE; // ⬜ nothing uploaded
    if (withFiles < found.length) return COLOR_YELLOW; // 🟡 some folders empty
    if (found.indexOf("სკანი") === -1 || found.indexOf("ფოტოები") === -1) {
        return COLOR_YELLOW; // 🟡 required folder missing
    }
    if (statuses === null) return null; // engine down — don't guess
    return statuses[actNumber] === true ? COLOR_GREEN : COLOR_YELLOW; // 🟢 only with signatures
}

// ---------- One ACT folder ----------

function processAct(actFolder, allState, statuses) {
    var actNumber = actFolder.getName();
    var actId = actFolder.getId();
    var state = readActState(allState, actId);
    var before = JSON.stringify(state);
    var now = Date.now();

    var found = [];
    var withFiles = 0;

    var subfolders = actFolder.getFolders();
    while (subfolders.hasNext()) {
        var sub = subfolders.next();
        var name = sub.getName();

        if (TRACKED_SUBFOLDERS.indexOf(name) === -1) continue; // კორესპონდენცია, ბიზნესი…

        found.push(name);
        var hasFiles = sub.getFiles().hasNext();
        if (hasFiles) withFiles++;

        state.sub[name] = checkSubfolder(
            actNumber,
            name,
            hasFiles,
            state.sub[name],
            now,
        );
        setColorIfChanged(
            sub.getId(),
            hasFiles ? COLOR_GREEN : COLOR_NONE,
            state,
        );
    }

    var rootColor = decideRootColor(actNumber, found, withFiles, statuses);
    if (rootColor !== null) setColorIfChanged(actId, rootColor, state);

    if (JSON.stringify(state) !== before) writeActState(actId, state);
}

// ---------- Entry point (5-min trigger) ----------
// Only one run at a time: if a run is already in progress
// (e.g. manual Run while the trigger fires), exit immediately.

function scanDriveFolders() {
    var lock = LockService.getScriptLock();

    if (!lock.tryLock(0)) {
        Logger.log("Another run in progress — skipping");
        return;
    }

    try {
        runSweep();
    } finally {
        lock.releaseLock();
    }
}

function runSweep() {
    var started = Date.now();
    var props = PropertiesService.getScriptProperties();
    var allState = loadAllState();
    var statuses = fetchSignatureStatuses();

    var actFolders;
    var token = props.getProperty("continuation");
    try {
        actFolders = token
            ? DriveApp.continueFolderIterator(token)
            : DriveApp.getFolderById(ROOT_FOLDER_ID).getFolders();
    } catch (e) {
        Logger.log("Continuation expired — restarting sweep");
        props.deleteProperty("continuation");
        actFolders = DriveApp.getFolderById(ROOT_FOLDER_ID).getFolders();
    }

    while (actFolders.hasNext()) {
        if (Date.now() - started > MAX_RUNTIME_MS) {
            props.setProperty(
                "continuation",
                actFolders.getContinuationToken(),
            );
            Logger.log("Time limit reached — will resume next run");
            return;
        }

        var actFolder = actFolders.next();
        try {
            processAct(actFolder, allState, statuses);
        } catch (e) {
            Logger.log("Error on ACT " + actFolder.getName() + ": " + e); // one bad ACT never stops the sweep
        }
    }

    props.deleteProperty("continuation");
    Logger.log("Full sweep complete");
}
