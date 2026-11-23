<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English strings for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @category   string
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['authorizedby'] = 'Authorized by';
$string['availableknowledgebases'] = 'Knowledge base';
$string['baseurl'] = 'FredKI® base URL';
$string['baseurl_desc'] = 'HTTPS endpoint of the external FredKI® service. Selected files and their Moodle context are transferred to this service.';
$string['baseurlnotconfigured'] = 'The FredKI® base URL has not been configured. Ask your site administrator.';
$string['chooseknowledgebase'] = 'Select a knowledge base';
$string['credentialsintro'] = 'Enter your FredKI® credentials once to load the available knowledge bases. The password is used only for authentication and is never stored.';
$string['debuglog'] = 'Enable debug logging';
$string['debuglog_desc'] = 'Writes limited diagnostic information (never credentials) to Moodle developer debugging. Do not enable in production.';
$string['defaultworkspace'] = 'Default workspace';
$string['defaultworkspace_desc'] = 'Default FredKI® workspace used by new activities.';
$string['documentcontextactivity'] = 'Moodle activity: {$a}';
$string['documentcontextcourse'] = 'Course: {$a}';
$string['documentcontextfile'] = 'File: {$a}';
$string['documentcontextheading'] = 'Moodle context for this document';
$string['documentcontextinstruction'] = 'The following sections contain the complete document. Use this Moodle context to understand its purpose and placement.';
$string['documentcontextsource'] = 'Source in Moodle: {$a}';
$string['documentcontexttitle'] = 'Title: {$a}';
$string['editfiles'] = 'Manage files';
$string['entercredentials'] = 'Enter a FredKI® username and password first.';
$string['filenotfound'] = 'The file could not be found in Moodle.';
$string['files'] = 'Files';
$string['fredkiapifailed'] = 'The FredKI® service could not process the request.';
$string['fredkidocumentid'] = 'FredKI® document ID';
$string['fredkipassword'] = 'FredKI® password';
$string['fredkisettingsheader'] = 'FredKI® access and knowledge base';
$string['fredkisync:addinstance'] = 'Add a new FredKI® Sync Directory';
$string['fredkisync:managefiles'] = 'Manage and synchronise files in a FredKI® Sync Directory';
$string['fredkisync:view'] = 'View a FredKI® Sync Directory';
$string['fredkiusername'] = 'FredKI® username';
$string['invalidbaseurl'] = 'The FredKI® base URL must be a valid HTTPS URL.';
$string['invalidrequestmethod'] = 'Invalid request method.';
$string['knowledgebase'] = 'Knowledge base';
$string['knowledgebaseid'] = 'Knowledge base ID';
$string['knowledgebasename'] = 'Knowledge base name';
$string['knowledgebaserequired'] = 'Load and select a knowledge base.';
$string['knowledgebasesloaded'] = 'knowledge base(s) loaded.';
$string['knowledgebasesloadfailed'] = 'The knowledge bases could not be loaded. Check your credentials.';
$string['lastsync'] = 'Last synchronisation';
$string['loadingknowledgebases'] = 'Loading knowledge bases …';
$string['loadknowledgebases'] = 'Load knowledge bases';
$string['loginpath'] = 'FredKI® login path';
$string['loginpath_desc'] = 'Relative path to the FredKI® login endpoint.';
$string['modulename'] = 'FredKI® Sync Directory';
$string['modulename_help'] = 'A FredKI® Sync Directory is a file folder in the course whose content is kept in sync with a FredKI® knowledge base.

Files that trainers upload, replace or delete here are uploaded to, updated in or removed from the connected knowledge base immediately after saving. AI agents that use this knowledge base can then answer questions based on the current course materials.

Supported file types: PDF, DOCX, TXT, Markdown, HTML and CSV.';
$string['modulenameplural'] = 'FredKI® Sync Directories';
$string['noactivities'] = 'There are no FredKI® Sync Directories in this course.';
$string['nofiles'] = 'No files yet.';
$string['noknowledgebasechosen'] = 'No knowledge base selected yet.';
$string['noknowledgebases'] = 'No knowledge bases were found for this account.';
$string['nosyncentries'] = 'No synchronisation records yet.';
$string['notconnected'] = 'Not connected';
$string['notconnectedinfo'] = 'This activity is not connected to a FredKI® knowledge base (for example after a course restore). Edit the activity settings to connect it, then save the files once to synchronise them.';
$string['pluginadministration'] = 'FredKI® Sync Directory administration';
$string['pluginname'] = 'FredKI® Sync Directory';
$string['privacy:externaldeletion'] = 'Removing a user authorisation deletes only the local access token. Documents are shared activity data and are not deleted when one user is removed. Removing files queues remote deletion. Deleting the Moodle activity removes local mappings and credentials but cannot guarantee remote deletion; administrators must complete deletion in FredKI® when required.';
$string['privacy:metadata:fredki'] = 'Selected files and their Moodle context are sent to the configured FredKI® service.';
$string['privacy:metadata:fredki:context'] = 'Course name, activity name and Moodle source URL.';
$string['privacy:metadata:fredki:files'] = 'Selected files, filenames and document contents.';
$string['privacy:metadata:fredki:username'] = 'The FredKI® username used to authorise the request.';
$string['privacy:metadata:map'] = 'Maps the activity files to FredKI® documents.';
$string['privacy:metadata:map:filename'] = 'The name of the synchronised file.';
$string['privacy:metadata:map:lastattempt'] = 'When the last synchronisation attempt took place.';
$string['privacy:metadata:map:syncuserid'] = 'The Moodle user whose FredKI® authorisation was used for the synchronisation.';
$string['privacy:metadata:userauth'] = 'Stores the FredKI® authorisation of a Moodle user for an activity.';
$string['privacy:metadata:userauth:activityid'] = 'The FredKI® Sync Directory ID.';
$string['privacy:metadata:userauth:lastused'] = 'When the locally stored access token was last used.';
$string['privacy:metadata:userauth:timecreated'] = 'When the authorisation was created.';
$string['privacy:metadata:userauth:timemodified'] = 'When the authorisation was last changed.';
$string['privacy:metadata:userauth:token'] = 'The encrypted FredKI® access token.';
$string['privacy:metadata:userauth:tokenexpires'] = 'When the locally stored access token expires.';
$string['privacy:metadata:userauth:userid'] = 'The Moodle user ID.';
$string['privacy:metadata:userauth:username'] = 'The FredKI® username.';
$string['privacy:path:authorisation'] = 'FredKI® authorisation';
$string['privacy:path:synchronisations'] = 'Synchronised files';
$string['selectedknowledgebase'] = 'Selected knowledge base:';
$string['selectedknowledgebasebutton'] = 'Selected';
$string['selectknowledgebasebutton'] = 'Select';
$string['settingslocked'] = 'This activity is already connected to a knowledge base. The connection is locked to prevent accidental reassignment.';
$string['status_deleted'] = 'Removal pending';
$string['status_error'] = 'Error';
$string['status_pending'] = 'Pending';
$string['status_synced'] = 'Synchronised';
$string['synccompletedimmediately'] = 'Files saved and synchronised with FredKI®: {$a->synced} uploaded, {$a->deleted} removed.';
$string['synccompletedwitherrors'] = 'Files saved. {$a->synced} uploaded and {$a->deleted} removed; {$a->errors} item(s) could not be synchronised. See the status table for details.';
$string['synccompletedwithretry'] = 'Files saved. {$a->synced} uploaded and {$a->deleted} removed immediately; {$a->queued} item(s) will be retried automatically.';
$string['syncgenericerror'] = 'The file could not be synchronised with FredKI®.';
$string['syncretrypending'] = 'Temporary FredKI® failure. Automatic retry {$a} of 5 has been scheduled.';
$string['syncstatus'] = 'Synchronisation status';
$string['taskpurgeexpiredtokens'] = 'Purge expired FredKI® access tokens';
$string['tokeninvalid'] = 'The FredKI® authorisation is missing, expired or no longer accepted. Edit the activity and enter your FredKI® credentials again.';
$string['tokenlifetime'] = 'Local token lifetime';
$string['tokenlifetime_desc'] = 'Maximum time an access token is retained locally. Set to zero only when FredKI® tokens have their own enforced expiry.';
