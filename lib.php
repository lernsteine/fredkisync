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
 * Core callbacks for the FredKI® Sync activity module.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_fredkisync\local\fredki_client;
use mod_fredkisync\local\token_manager;

/**
 * Reports supported Moodle features.
 *
 * @param string $feature Feature constant.
 * @return mixed True, false, a purpose constant or null if unknown.
 */
function fredkisync_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Resolves the selected knowledge-base name from submitted values.
 *
 * @param stdClass $data Submitted form data.
 * @return string Knowledge-base name, or its ID as a fallback.
 */
function fredkisync_resolve_knowledge_base_name(stdClass $data): string {
    global $SESSION;

    $submitted = trim((string)($data->knowledgebasename ?? ''));
    if ($submitted !== '') {
        return $submitted;
    }

    $knowledgeid = trim((string)($data->knowledgeid ?? ''));
    $courseid = (int)($data->course ?? 0);
    if ($courseid && isset($SESSION->fredkisync_knowledgebases[$courseid][$knowledgeid])) {
        return (string)$SESSION->fredkisync_knowledgebases[$courseid][$knowledgeid];
    }

    return $knowledgeid;
}

/**
 * Authenticates against FredKI® and stores the resulting access token.
 *
 * The password is only used for this request and is never stored.
 *
 * @param int $activityid Activity instance ID.
 * @param string $username FredKI® username.
 * @param string $password FredKI® password.
 * @param string $workspace FredKI® workspace.
 */
function fredkisync_store_user_auth(int $activityid, string $username, string $password, string $workspace): void {
    global $USER;
    if ($username === '' || $password === '') {
        return;
    }
    $config = get_config('mod_fredkisync');
    $client = new fredki_client(
        $config->baseurl ?? '',
        $workspace,
        $username,
        $password,
        $config->loginpath ?? '/admin/api/user/login',
        !empty($config->debuglog)
    );
    token_manager::store($activityid, (int)$USER->id, $username, $client->login());
}

/**
 * Creates a FredKI® Sync activity.
 *
 * @param stdClass $data Form data.
 * @param mod_fredkisync_mod_form|null $mform The form.
 * @return int New instance ID.
 */
function fredkisync_add_instance($data, $mform = null): int {
    global $DB;
    $username = trim((string)($data->fredkiusername ?? ''));
    $password = (string)($data->fredkipassword ?? '');
    $data->workspace = trim((string)($data->workspace ?? 'default')) ?: 'default';
    $data->knowledgebasename = fredkisync_resolve_knowledge_base_name($data);
    $data->syncenabled = !empty($data->syncenabled);
    $data->timecreated = time();
    $data->timemodified = time();
    unset($data->fredkiusername, $data->fredkipassword, $data->knowledgebaseselector);
    $id = $DB->insert_record('fredkisync', $data);
    fredkisync_store_user_auth($id, $username, $password, $data->workspace);
    return $id;
}

/**
 * Updates a FredKI® Sync activity.
 *
 * Once a knowledge base is connected, the connection is kept to prevent accidental reassignment.
 *
 * @param stdClass $data Form data.
 * @param mod_fredkisync_mod_form|null $mform The form.
 * @return bool
 */
function fredkisync_update_instance($data, $mform = null): bool {
    global $DB;
    $existing = $DB->get_record('fredkisync', ['id' => $data->instance], '*', MUST_EXIST);
    $username = trim((string)($data->fredkiusername ?? ''));
    $password = (string)($data->fredkipassword ?? '');
    if (!empty($existing->knowledgeid)) {
        $data->knowledgeid = $existing->knowledgeid;
        $data->knowledgebasename = $existing->knowledgebasename;
        $data->workspace = $existing->workspace;
    }
    $data->id = $data->instance;
    $data->workspace = trim((string)($data->workspace ?? 'default')) ?: 'default';
    $data->knowledgebasename = fredkisync_resolve_knowledge_base_name($data);
    $data->syncenabled = !empty($data->syncenabled);
    $data->timemodified = time();
    unset($data->fredkiusername, $data->fredkipassword, $data->knowledgebaseselector);
    $result = $DB->update_record('fredkisync', $data);
    if ($username !== '' && $password !== '') {
        fredkisync_store_user_auth($data->id, $username, $password, $data->workspace);
    }
    return $result;
}

/**
 * Deletes an activity and its local credentials, mappings and files.
 *
 * @param int $id Instance ID.
 * @return bool
 */
function fredkisync_delete_instance($id): bool {
    global $DB;
    $activity = $DB->get_record('fredkisync', ['id' => $id]);
    if (!$activity) {
        return false;
    }
    $cm = get_coursemodule_from_instance('fredkisync', $id, 0, false, IGNORE_MISSING);
    if ($cm) {
        get_file_storage()->delete_area_files(context_module::instance($cm->id)->id, 'mod_fredkisync', 'fredkisync_files');
    }
    token_manager::revoke_activity($activity->id);
    $DB->delete_records('fredkisync_map', ['activityid' => $activity->id]);
    $DB->delete_records('fredkisync', ['id' => $activity->id]);
    return true;
}

/**
 * Marks the activity as viewed and triggers the course_module_viewed event.
 *
 * @param stdClass $activity Activity record.
 * @param stdClass $course Course record.
 * @param cm_info|stdClass $cm Course module.
 * @param context_module $context Module context.
 */
function fredkisync_view(stdClass $activity, stdClass $course, $cm, context_module $context): void {
    $event = \mod_fredkisync\event\course_module_viewed::create([
        'objectid' => $activity->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('fredkisync', $activity);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Serves files stored by the activity.
 *
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array $args Remaining file path arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool False if the file was not found.
 */
function fredkisync_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if (!$context instanceof context_module || $filearea !== 'fredkisync_files') {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/fredkisync:view', $context);
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_fredkisync', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}
