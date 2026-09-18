const WEBHOOK_URL = "https://vigil.45-32-154-101.nip.io/webhook/drive";
const WEBHOOK_SECRET = "YOUR_WEBHOOK_SECRET_HERE";
const ROOT_FOLDER_ID = "1UzSEitUL8gzCoLVZtmPXKh8wczDz1ggM";

/**
 * Trigger this function on Drive changes.
 * Set up: Triggers → Add Trigger → onDriveChange → Drive → onChange
 */
function onDriveChange(event) {
    try {
        var fileId = event.fileId;
        var file = DriveApp.getFileById(fileId);
        var changeType = event.changeType; // 'added', 'edited', 'removed'

        // Ignore deletions for now
        if (changeType === "removed") {
            return;
        }

        // Get the parent folder (e.g. ფოტოები)
        var parents = file.getParents();
        if (!parents.hasNext()) {
            return;
        }
        var subfolder = parents.next();
        var subfolderName = subfolder.getName();

        // Get the grandparent folder (e.g. 329)
        var grandparents = subfolder.getParents();
        if (!grandparents.hasNext()) {
            return;
        }
        var actFolder = grandparents.next();
        var actFolderName = actFolder.getName();

        // Verify this is inside our root folder
        var rootParents = actFolder.getParents();
        if (!rootParents.hasNext()) {
            return;
        }
        var rootFolder = rootParents.next();
        if (rootFolder.getId() !== ROOT_FOLDER_ID) {
            return;
        }

        // Send to our engine
        var payload = JSON.stringify({
            act_number: actFolderName,
            subfolder: subfolderName,
            change_type: changeType,
        });

        UrlFetchApp.fetch(WEBHOOK_URL + "?secret=" + WEBHOOK_SECRET, {
            method: "post",
            contentType: "application/json",
            payload: payload,
            muteHttpExceptions: true,
        });
    } catch (e) {
        Logger.log("Vigil Drive Watcher error: " + e.toString());
    }
}
