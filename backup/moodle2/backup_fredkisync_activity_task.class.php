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
 * Backup task for the FredKI® Sync activity.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/fredkisync/backup/moodle2/backup_fredkisync_stepslib.php');

/**
 * Backup task for FredKI® Sync.
 */
class backup_fredkisync_activity_task extends backup_activity_task {
    /**
     * Defines settings. The activity has none.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_fredkisync_activity_structure_step('fredkisync_structure', 'fredkisync.xml'));
    }

    /**
     * Encodes links to the activity so they can be restored.
     *
     * @param string $content Content.
     * @return string Encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');
        $search = '/(' . $base . '\/mod\/fredkisync\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@FREDKISYNCINDEX*$2@$', $content);
        $search = '/(' . $base . '\/mod\/fredkisync\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@FREDKISYNCVIEWBYID*$2@$', $content);
        return $content;
    }
}
