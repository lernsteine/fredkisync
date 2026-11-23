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
 * Immediate synchronisation service for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\local;

use mod_fredkisync\task\sync_activity;

/**
 * Runs pending synchronisation work directly in the current request.
 */
class immediate_sync_service {
    /**
     * Synchronises all pending and deleted mappings for an activity.
     *
     * Temporary API failures are retained for the existing ad-hoc retry task.
     * Permanent failures remain visible in the activity status table.
     *
     * @param \stdClass $activity Activity record.
     * @param int $userid Moodle user whose FredKI® token must be used.
     * @return array{synced:int, deleted:int, errors:int, queued:int}
     */
    public static function run(\stdClass $activity, int $userid): array {
        global $DB;

        $result = [
            'synced' => 0,
            'deleted' => 0,
            'errors' => 0,
            'queued' => 0,
        ];
        $requiresretry = false;
        $nextretrytime = 0;

        foreach (['pending', 'deleted'] as $requestedstatus) {
            $records = $DB->get_records(
                'fredkisync_map',
                ['activityid' => $activity->id, 'status' => $requestedstatus],
                'timecreated ASC'
            );

            foreach ($records as $record) {
                try {
                    if ($requestedstatus === 'pending') {
                        manual_sync_service::sync_record($activity, $record, $userid);
                    } else {
                        manual_sync_service::delete_record($activity, $record, $userid);
                    }
                } catch (api_exception $exception) {
                    // The manual sync service has already stored the retry state.
                    if ($exception->is_transient()) {
                        $requiresretry = true;
                        $freshretry = $DB->get_record(
                            'fredkisync_map',
                            ['id' => $record->id],
                            'nextruntime',
                            IGNORE_MISSING
                        );
                        if ($freshretry && !empty($freshretry->nextruntime)) {
                            $nextretrytime = $nextretrytime === 0
                                ? (int)$freshretry->nextruntime
                                : min($nextretrytime, (int)$freshretry->nextruntime);
                        }
                        $result['queued']++;
                        continue;
                    }
                }

                $fresh = $DB->get_record('fredkisync_map', ['id' => $record->id], 'status', IGNORE_MISSING);
                if (!$fresh) {
                    if ($requestedstatus === 'deleted') {
                        $result['deleted']++;
                    }
                    continue;
                }

                if ($fresh->status === 'synced') {
                    $result['synced']++;
                } else if ($fresh->status === 'error') {
                    $result['errors']++;
                } else if ($fresh->status === 'pending' || $fresh->status === 'deleted') {
                    $requiresretry = true;
                    $retryrecord = $DB->get_record(
                        'fredkisync_map',
                        ['id' => $record->id],
                        'nextruntime',
                        IGNORE_MISSING
                    );
                    if ($retryrecord && !empty($retryrecord->nextruntime)) {
                        $nextretrytime = $nextretrytime === 0
                            ? (int)$retryrecord->nextruntime
                            : min($nextretrytime, (int)$retryrecord->nextruntime);
                    }
                    $result['queued']++;
                }
            }
        }

        if ($requiresretry) {
            $task = new sync_activity();
            $task->set_component('mod_fredkisync');
            $task->set_custom_data([
                'activityid' => (int)$activity->id,
                'userid' => $userid,
            ]);
            if ($nextretrytime > time()) {
                $task->set_next_run_time($nextretrytime);
            }
            \core\task\manager::queue_adhoc_task($task, true);
        }

        return $result;
    }
}
