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
 * Backup structure for the FredKI® Sync activity.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Defines the FredKI® Sync backup structure.
 *
 * Credentials, file mappings and external document identifiers are never backed up.
 */
class backup_fredkisync_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $activity = new backup_nested_element('fredkisync', ['id'], [
            'name', 'intro', 'introformat', 'knowledgeid', 'knowledgebasename', 'workspace',
            'syncenabled', 'timecreated', 'timemodified',
        ]);
        $activity->set_source_table('fredkisync', ['id' => backup::VAR_ACTIVITYID]);
        $activity->annotate_files('mod_fredkisync', 'intro', null);
        $activity->annotate_files('mod_fredkisync', 'fredkisync_files', null);
        return $this->prepare_activity_structure($activity);
    }
}
