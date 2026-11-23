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
 * File management page for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use mod_fredkisync\form\managefiles_form;
use mod_fredkisync\local\file_sync_helper;
use mod_fredkisync\local\immediate_sync_service;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('fredkisync', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('fredkisync', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/fredkisync:managefiles', $context);

$PAGE->set_url('/mod/fredkisync/managefiles.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($activity);

$fileoptions = [
    'subdirs' => 0,
    'maxbytes' => 0,
    'maxfiles' => 50,
    'accepted_types' => ['.pdf', '.docx', '.txt', '.md', '.html', '.htm', '.csv'],
];
$draftitemid = file_get_submitted_draft_itemid('files_filemanager');
file_prepare_draft_area($draftitemid, $context->id, 'mod_fredkisync', 'fredkisync_files', 0, $fileoptions);
$mform = new managefiles_form(new moodle_url('/mod/fredkisync/managefiles.php', ['id' => $cm->id]), [
    'id' => $cm->id,
    'fileoptions' => $fileoptions,
]);
$mform->set_data((object)['id' => $cm->id, 'files_filemanager' => $draftitemid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/fredkisync/view.php', ['id' => $cm->id]));
}
if ($data = $mform->get_data()) {
    file_save_draft_area_files($data->files_filemanager, $context->id, 'mod_fredkisync', 'fredkisync_files', 0, $fileoptions);
    file_sync_helper::scan_activity_files($cm->id, $activity->id);

    if (empty($activity->knowledgeid)) {
        redirect(
            new moodle_url('/mod/fredkisync/view.php', ['id' => $cm->id]),
            get_string('notconnectedinfo', 'mod_fredkisync'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    // Uploading and splitting several documents can take a while.
    core_php_time_limit::raise(600);
    $syncresult = immediate_sync_service::run($activity, (int)$USER->id);
    $messageparams = (object)[
        'synced' => $syncresult['synced'],
        'deleted' => $syncresult['deleted'],
        'errors' => $syncresult['errors'],
        'queued' => $syncresult['queued'],
    ];

    if ($syncresult['errors'] > 0) {
        $message = get_string('synccompletedwitherrors', 'mod_fredkisync', $messageparams);
        $messagetype = \core\output\notification::NOTIFY_WARNING;
    } else if ($syncresult['queued'] > 0) {
        $message = get_string('synccompletedwithretry', 'mod_fredkisync', $messageparams);
        $messagetype = \core\output\notification::NOTIFY_INFO;
    } else {
        $message = get_string('synccompletedimmediately', 'mod_fredkisync', $messageparams);
        $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    }

    redirect(
        new moodle_url('/mod/fredkisync/view.php', ['id' => $cm->id]),
        $message,
        null,
        $messagetype
    );
}

echo $OUTPUT->header();
echo html_writer::div(html_writer::link(new moodle_url('/mod/fredkisync/view.php', ['id' => $cm->id]), get_string('back')), 'mb-3');
$mform->display();
echo $OUTPUT->footer();
