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
 * Restore structure for the FredKI® Sync activity.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Restores FredKI® Sync activities.
 *
 * The restored activity is not connected to a knowledge base. A trainer connects it
 * again and saves the file manager once to synchronise the restored files.
 */
class restore_fredkisync_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [new restore_path_element('fredkisync', '/activity/fredkisync')];
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity record without its knowledge-base connection.
     *
     * @param array $data Activity data.
     */
    protected function process_fredkisync($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->knowledgeid = '';
        $data->knowledgebasename = '';
        $data->timemodified = time();
        $newitemid = $DB->insert_record('fredkisync', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores the files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_fredkisync', 'intro', null);
        $this->add_related_files('mod_fredkisync', 'fredkisync_files', null);
    }
}
