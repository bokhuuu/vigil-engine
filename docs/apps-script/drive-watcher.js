const WEBHOOK_URL = "https://vigil.45-32-154-101.nip.io/webhook/drive";
const WEBHOOK_SECRET =
    "b964cf127693707f8aa57a507a01e58264be2e9dbdeab8d6c97bf142d5c11b57";
const ROOT_FOLDER_ID = "1UzSEitUL8gzCoLVZtmPXKh8wczDz1ggM";

// Subfolders that trigger Airtable checkbox updates
const TRACKED_SUBFOLDERS = [
    "შიდა ნახაზები",
    "გამიჯვნები",
    "ხარჯთაღრიცხვა",
    "ტოპო ნარგავები",
];

/**
 * Runs every 5 minutes via time-driven trigger.
 * Scans all ACT folders, finds subfolders with files,
 * sends webhook for each non-empty tracked subfolder.
 */
function scanDriveFolders() {
    try {
        var rootFolder = DriveApp.getFolderById(ROOT_FOLDER_ID);
        var actFolders = rootFolder.getFolders();

        while (actFolders.hasNext()) {
            var actFolder = actFolders.next();
            var actNumber = actFolder.getName();

            var subfolders = actFolder.getFolders();

            while (subfolders.hasNext()) {
                var subfolder = subfolders.next();
                var subfolderName = subfolder.getName();

                // Only process tracked subfolders
                if (TRACKED_SUBFOLDERS.indexOf(subfolderName) === -1) {
                    continue;
                }

                // Check if subfolder has any files
                var files = subfolder.getFiles();
                if (!files.hasNext()) {
                    continue;
                }

                // Send webhook
                var payload = JSON.stringify({
                    act_number: actNumber,
                    subfolder: subfolderName,
                    change_type: "added",
                });

                UrlFetchApp.fetch(WEBHOOK_URL + "?secret=" + WEBHOOK_SECRET, {
                    method: "post",
                    contentType: "application/json",
                    payload: payload,
                    muteHttpExceptions: true,
                });

                Logger.log(
                    "Sent webhook: ACT " + actNumber + " / " + subfolderName,
                );
            }
        }
    } catch (e) {
        Logger.log("Vigil Drive Watcher error: " + e.toString());
    }
}
