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
 * Lists all FredKI® Sync activities in a course.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);

require_course_login($course);
$context = context_course::instance($course->id);

$event = \mod_fredkisync\event\course_module_instance_list_viewed::create(['context' => $context]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/fredkisync/index.php', ['id' => $course->id]);
$PAGE->set_title(format_string($course->shortname) . ': ' . get_string('modulenameplural', 'mod_fredkisync'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('modulenameplural', 'mod_fredkisync'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_fredkisync'));

$activities = get_all_instances_in_course('fredkisync', $course);
if (empty($activities)) {
    notice(get_string('noactivities', 'mod_fredkisync'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$table->head = [get_string('name'), get_string('knowledgebase', 'mod_fredkisync')];
foreach ($activities as $activity) {
    $attributes = $activity->visible ? [] : ['class' => 'dimmed'];
    $link = html_writer::link(
        new moodle_url('/mod/fredkisync/view.php', ['id' => $activity->coursemodule]),
        format_string($activity->name, true, ['context' => context_module::instance($activity->coursemodule)]),
        $attributes
    );
    $table->data[] = [$link, s($activity->knowledgebasename ?: get_string('notconnected', 'mod_fredkisync'))];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
