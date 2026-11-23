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
 * Restore task for the FredKI® Sync activity.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/fredkisync/backup/moodle2/restore_fredkisync_stepslib.php');

/**
 * Restore task for FredKI® Sync.
 */
class restore_fredkisync_activity_task extends restore_activity_task {
    /**
     * Defines settings. The activity has none.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_fredkisync_activity_structure_step('fredkisync_structure', 'fredkisync.xml'));
    }

    /**
     * Defines the contents that contain encoded links.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('fredkisync', ['intro'], 'fredkisync'),
        ];
    }

    /**
     * Defines the link decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('FREDKISYNCVIEWBYID', '/mod/fredkisync/view.php?id=$1', 'course_module'),
            new restore_decode_rule('FREDKISYNCINDEX', '/mod/fredkisync/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Defines the restore log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('fredkisync', 'view', 'view.php?id={course_module}', '{fredkisync}'),
        ];
    }

    /**
     * Defines the restore log rules for course-level entries.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('fredkisync', 'view all', 'index.php?id={course}', null),
        ];
    }
}
