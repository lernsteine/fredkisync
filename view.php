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
 * Main view page for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('fredkisync', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$activity = $DB->get_record('fredkisync', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/fredkisync:view', $context);

fredkisync_view($activity, $course, $cm, $context);

$PAGE->set_url('/mod/fredkisync/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($activity);

$canmanage = has_capability('mod/fredkisync:managefiles', $context);

echo $OUTPUT->header();

if (empty($activity->knowledgeid)) {
    if ($canmanage) {
        echo $OUTPUT->notification(get_string('notconnectedinfo', 'mod_fredkisync'), 'warning');
    }
} else {
    $summary = get_string('knowledgebase', 'mod_fredkisync') . ': ' .
        s($activity->knowledgebasename ?: $activity->knowledgeid);
    echo html_writer::div($summary, 'fredkisync-knowledge-summary');
}

if ($canmanage) {
    $editurl = new moodle_url('/mod/fredkisync/managefiles.php', ['id' => $cm->id]);
    echo html_writer::div(
        html_writer::link($editurl, get_string('editfiles', 'mod_fredkisync'), ['class' => 'btn btn-secondary']),
        'fredkisync-actions'
    );
}

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_fredkisync', 'fredkisync_files', 0, 'filename', false);

echo $OUTPUT->heading(get_string('files', 'mod_fredkisync'), 3);
if (empty($files)) {
    echo $OUTPUT->notification(get_string('nofiles', 'mod_fredkisync'), 'info');
} else {
    $items = [];
    foreach ($files as $file) {
        $url = moodle_url::make_pluginfile_url(
            $context->id,
            'mod_fredkisync',
            'fredkisync_files',
            0,
            $file->get_filepath(),
            $file->get_filename()
        );
        $items[] = html_writer::link($url, s($file->get_filename())) . ' (' . display_size($file->get_filesize()) . ')';
    }
    echo html_writer::alist($items);
}

if ($canmanage) {
    echo $OUTPUT->heading(get_string('syncstatus', 'mod_fredkisync'), 3);
    $records = $DB->get_records('fredkisync_map', ['activityid' => $activity->id], 'filename ASC');
    if (empty($records)) {
        echo $OUTPUT->notification(get_string('nosyncentries', 'mod_fredkisync'), 'info');
    } else {
        $statuslabels = [
            'pending' => get_string('status_pending', 'mod_fredkisync'),
            'synced' => get_string('status_synced', 'mod_fredkisync'),
            'deleted' => get_string('status_deleted', 'mod_fredkisync'),
            'error' => get_string('status_error', 'mod_fredkisync'),
        ];
        $table = new html_table();
        $table->head = [
            get_string('file'),
            get_string('status'),
            get_string('lastsync', 'mod_fredkisync'),
            get_string('fredkidocumentid', 'mod_fredkisync'),
            get_string('error'),
        ];
        foreach ($records as $record) {
            $table->data[] = [
                s($record->filename),
                s($statuslabels[$record->status] ?? $record->status),
                !empty($record->timesynced) ? userdate($record->timesynced) : '-',
                s($record->fredki_document_id ?? ''),
                !empty($record->lasterror) ? s($record->lasterror) : '',
            ];
        }
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
