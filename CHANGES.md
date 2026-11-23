# Changes

## 1.2.0 (2026-11-23)

- First Moodle Plugins directory release (stable).
- Supports Moodle 4.5 to 5.3.
- Added `index.php`, view events and activity completion on view.
- Access tokens are now encrypted at rest; existing tokens are encrypted during the upgrade.
- A changed file is now uploaded before the previous version is removed, so a failed upload no longer loses the document.
- Documents that already vanished in FredKI® are treated as removed.
- Saving the file manager again retries files that failed before.
- Privacy API now also covers the user reference stored for background synchronisation.
- Backups no longer contain file mappings; restored activities are reconnected and synchronised from scratch.
- Synchronisation details are only shown to users who can manage the files.
- Removed the deprecated knowledge-base endpoint, the unused `mod/fredkisync:sync` capability and unused strings.
- Fixed the PHPUnit data generator, added tests, coding style and documentation.
- German translation moved out of the package for contribution via AMOS.

## 1.1.7-rc1

- Synchronises new, changed and removed files immediately after saving the file manager.
- Keeps the existing ad-hoc task only as a retry fallback for temporary FredKI or network failures.
- Uses success/warning feedback instead of an always-blue queued notification.
- Makes the knowledge-base information panel visually more subtle.

## 1.1.6

- Persist AJAX-loaded knowledge bases in the Moodle session so QuickForm can validate and restore the selected option on submit.
- Restore the selector as a native MoodleQuickForm select populated server-side on every request.
- Resolve the selected knowledge-base name from the server-side session as a reliable fallback.

## 1.1.5

- Replaced the AJAX-populated QuickForm select with a neutral HTML selector.
- Reliably synchronises knowledge-base ID and name before form submission.
- Added explicit feedback when no knowledge bases are returned.

## 1.1.4 (2026-07-29)

- Fixed persistence of dynamically loaded knowledge-base selections.
- Separated the AJAX-populated display selector from the stored `knowledgeid` field.
- Added explicit synchronisation of the selected knowledge-base ID and name.

## 1.1.3

- Fixed the XMLDB upgrade for `pathnamehash` by temporarily removing and restoring its unique index.

## 1.1.2

- Removed invalid empty-string defaults from mandatory XMLDB character fields.
- Added an upgrade step to normalise the corresponding database defaults.

## 1.1.0 — 2026-07-29

- Raised the minimum supported version to Moodle 4.5.
- Replaced inline JavaScript and the custom JSON endpoint with `core/ajax` and an external function.
- Moved file synchronisation to an ad-hoc task.
- Added backup and restore support without credentials or external document identifiers.
- Added token expiry, last-use tracking and scheduled cleanup.
- Removed obsolete username and password columns from the activity table.
- Extended the Privacy API with bulk deletion support.
- Added initial PHPUnit coverage and Moodle-style documentation.

## 1.1.1 — 2026-07-29

- Bound every ad-hoc synchronisation task to the Moodle user who queued it.
- Removed fallback to another user's access token during cron execution.
- Added retry metadata and exponential retry scheduling for temporary API failures.
- Classified authentication and other permanent failures without automatic retry.
- Clarified local and external deletion responsibilities in the privacy documentation.
