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
 * Ad-hoc task for synchronising an activity with FredKI®.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\task;

use core\task\adhoc_task;
use mod_fredkisync\local\api_exception;
use mod_fredkisync\local\manual_sync_service;

/**
 * Processes pending and deleted mappings outside the web request.
 */
class sync_activity extends adhoc_task {
    /**
     * Executes the task.
     */
    public function execute(): void {
        global $DB;

        $data = $this->get_custom_data();
        $activityid = (int)($data->activityid ?? 0);
        $userid = (int)($data->userid ?? 0);
        if (!$activityid || !$userid) {
            return;
        }

        $activity = $DB->get_record('fredkisync', ['id' => $activityid], '*', IGNORE_MISSING);
        if (!$activity || empty($activity->syncenabled)) {
            return;
        }

        $now = time();
        $params = ['activityid' => $activityid, 'now' => $now];
        $select = 'activityid = :activityid AND status = :status AND (nextruntime = 0 OR nextruntime <= :now)';
        $retrydelay = 0;

        foreach (['pending', 'deleted'] as $status) {
            $params['status'] = $status;
            $records = $DB->get_records_select('fredkisync_map', $select, $params, 'timecreated ASC');
            foreach ($records as $record) {
                try {
                    if ($status === 'pending') {
                        manual_sync_service::sync_record($activity, $record, $userid);
                    } else {
                        manual_sync_service::delete_record($activity, $record, $userid);
                    }
                } catch (api_exception $exception) {
                    if ($exception->is_transient()) {
                        $fresh = $DB->get_record('fredkisync_map', ['id' => $record->id], 'nextruntime', IGNORE_MISSING);
                        if ($fresh) {
                            $retrydelay = max($retrydelay, max(60, (int)$fresh->nextruntime - time()));
                        }
                    }
                }
            }
        }

        if ($retrydelay > 0) {
            $task = new self();
            $task->set_component('mod_fredkisync');
            $task->set_custom_data(['activityid' => $activityid, 'userid' => $userid]);
            $task->set_next_run_time(time() + $retrydelay);
            \core\task\manager::queue_adhoc_task($task, true);
        }
    }
}
