# FredKI<sup>®</sup> Sync Directory (mod_fredkisync)

FredKI<sup>®</sup> Sync Directory is a Moodle activity that turns a course folder into the live source of a FredKI<sup>®</sup> knowledge base.

Trainers upload course materials into the activity. As soon as they save, every new or changed file is uploaded to the connected FredKI<sup>®</sup> knowledge base and every removed file is deleted from it. AI agents that use this knowledge base therefore always answer on the basis of the current course materials.

## Features

- One activity = one folder = one FredKI<sup>®</sup> knowledge base.
- Immediate synchronisation when the file manager is saved (new, changed and removed files).
- Automatic background retries with increasing intervals (1, 2, 4, 8 minutes) for temporary network or service failures.
- Each document receives a short Moodle context paragraph (course, activity, file, source URL), so agents know where the material belongs.
- Status table for trainers showing the synchronisation state of every file.
- Supported file types: PDF, DOCX, TXT, Markdown, HTML and CSV.
- Backup and restore, Privacy API, events and activity completion on view.

## Requirements

- Moodle 4.5 to 5.3
- A FredKI<sup>®</sup> server reachable by the Moodle server via HTTPS
- Moodle cron running regularly (used for retries and token clean-up)

## About FredKI<sup>®</sup>

FredKI<sup>®</sup> is an open AI platform for learning agents and knowledge bases. It is freely available and can be self-hosted: <https://github.com/lernsteine/FredKI>. Further information: <https://fredki.com>.

The plugin does not require any paid service. Optionally, LERNSTEINE<sup>®</sup> offers hosted FredKI<sup>®</sup> systems.

## Installation

1. Copy the `fredkisync` folder to `mod/fredkisync` (Moodle 5.1 and later: `public/mod/fredkisync`), or install the ZIP via *Site administration > Plugins > Install plugins*.
2. Visit *Site administration > Notifications* to complete the installation.
3. Under *Site administration > Plugins > Activity modules > FredKI<sup>®</sup> Sync Directory*, enter the HTTPS base URL of your FredKI<sup>®</sup> server.

## Usage

1. Add the activity *FredKI<sup>®</sup> Sync Directory* to a course.
2. Enter your FredKI<sup>®</sup> username and password, click *Load knowledge bases* and select a knowledge base. Save.
3. Open the activity, click *Manage files*, upload the materials and save. The files are synchronised immediately.

Once connected, the knowledge base of an activity is locked to prevent accidental reassignment.

## Data protection

- The FredKI<sup>®</sup> password is only used to obtain an access token and is never stored.
- The access token is stored per Moodle user and activity, encrypted with Moodle's encryption API, expires after the configured lifetime and is removed by a scheduled task.
- Background retries only ever use the token of the user who started the synchronisation; there is no fallback to another user's token.
- Files, filenames and their Moodle context (course name, activity name, source URL) are transferred to the configured FredKI<sup>®</sup> server. Sites must include this server in their data-processing documentation.
- Backups contain the files but no credentials, mappings or external document IDs. A restored activity must be connected again.
- Deleting files deletes them from the knowledge base. Deleting the whole activity removes local data only; remove the files first (or use FredKI<sup>®</sup> administration) if the remote documents must be deleted too.

## Support

- Issues: <https://github.com/lernsteine/fredkisync/issues>
- Website: <https://fredki.com>

## License

2026 Ralf Hagemeister, LERNSTEINE<sup>®</sup> <https://lernsteine.com>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version. See [LICENSE](LICENSE).
