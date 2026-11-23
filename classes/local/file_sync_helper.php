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
 * File mapping helper for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync\local;

/**
 * Compares the activity file area with the stored mappings.
 */
class file_sync_helper {
    /**
     * Marks new or changed files as pending and removed files as deleted.
     *
     * @param int $cmid Course module ID.
     * @param int $activityid Activity instance ID.
     */
    public static function scan_activity_files(int $cmid, int $activityid): void {
        global $DB;

        $context = \context_module::instance($cmid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_fredkisync', 'fredkisync_files', 0, 'filename', false);
        $currenthashes = [];

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $pathnamehash = $file->get_pathnamehash();
            $currenthashes[] = $pathnamehash;
            $existing = $DB->get_record('fredkisync_map', ['activityid' => $activityid, 'pathnamehash' => $pathnamehash]);

            if (!$existing) {
                $record = (object)[
                    'cmid' => $cmid,
                    'activityid' => $activityid,
                    'pathnamehash' => $pathnamehash,
                    'filename' => $file->get_filename(),
                    'contenthash' => $file->get_contenthash(),
                    'filesize' => $file->get_filesize(),
                    'status' => 'pending',
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $DB->insert_record('fredkisync_map', $record);
                continue;
            }

            $changed = $existing->contenthash !== $file->get_contenthash() ||
                       $existing->filename !== $file->get_filename() ||
                       (int)$existing->filesize !== (int)$file->get_filesize();

            // Saving the file manager again also retries files that failed before.
            if ($changed || $existing->status === 'error') {
                $existing->filename = $file->get_filename();
                $existing->contenthash = $file->get_contenthash();
                $existing->filesize = $file->get_filesize();
                $existing->status = 'pending';
                $existing->lasterror = null;
                $existing->attempts = 0;
                $existing->nextruntime = 0;
                $existing->timemodified = time();
                $DB->update_record('fredkisync_map', $existing);
            }
        }

        $known = $DB->get_records('fredkisync_map', ['activityid' => $activityid]);
        foreach ($known as $record) {
            if (!in_array($record->pathnamehash, $currenthashes, true)) {
                if ($record->status !== 'deleted') {
                    $record->status = 'deleted';
                    $record->timemodified = time();
                    $DB->update_record('fredkisync_map', $record);
                }
            }
        }
    }
}
