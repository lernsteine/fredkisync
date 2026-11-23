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
 * Privacy provider for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Describes and manages personal data used by FredKI® Sync.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the stored and transferred personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('fredkisync_userauth', [
            'userid' => 'privacy:metadata:userauth:userid',
            'fredkisyncid' => 'privacy:metadata:userauth:activityid',
            'fredkiusername' => 'privacy:metadata:userauth:username',
            'token' => 'privacy:metadata:userauth:token',
            'tokenexpires' => 'privacy:metadata:userauth:tokenexpires',
            'lastused' => 'privacy:metadata:userauth:lastused',
            'timecreated' => 'privacy:metadata:userauth:timecreated',
            'timemodified' => 'privacy:metadata:userauth:timemodified',
        ], 'privacy:metadata:userauth');
        $collection->add_database_table('fredkisync_map', [
            'syncuserid' => 'privacy:metadata:map:syncuserid',
            'filename' => 'privacy:metadata:map:filename',
            'lastattempt' => 'privacy:metadata:map:lastattempt',
        ], 'privacy:metadata:map');
        $collection->add_external_location_link('fredki', [
            'username' => 'privacy:metadata:fredki:username',
            'files' => 'privacy:metadata:fredki:files',
            'context' => 'privacy:metadata:fredki:context',
            'deletion' => 'privacy:externaldeletion',
        ], 'privacy:metadata:fredki');
        return $collection;
    }

    /**
     * Returns the contexts that contain data for a user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = ['modname' => 'fredkisync', 'contextlevel' => CONTEXT_MODULE, 'userid' => $userid];
        $sql = "SELECT ctx.id
                  FROM {fredkisync_userauth} ua
                  JOIN {course_modules} cm ON cm.instance = ua.fredkisyncid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ua.userid = :userid";
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {fredkisync_map} fm
                  JOIN {course_modules} cm ON cm.instance = fm.activityid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE fm.syncuserid = :userid";
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Adds the users who have data in a context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof \context_module) {
            return;
        }
        $params = ['modname' => 'fredkisync', 'cmid' => $userlist->get_context()->instanceid];
        $sql = "SELECT ua.userid
                  FROM {fredkisync_userauth} ua
                  JOIN {course_modules} cm ON cm.instance = ua.fredkisyncid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, $params);
        $sql = "SELECT fm.syncuserid
                  FROM {fredkisync_map} fm
                  JOIN {course_modules} cm ON cm.instance = fm.activityid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid AND fm.syncuserid > 0";
        $userlist->add_from_sql('syncuserid', $sql, $params);
    }

    /**
     * Exports personal data. The access token itself is never exported.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('fredkisync', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $auth = $DB->get_record('fredkisync_userauth', ['userid' => $userid, 'fredkisyncid' => $cm->instance]);
            if ($auth) {
                $path = [get_string('privacy:path:authorisation', 'mod_fredkisync')];
                writer::with_context($context)->export_data($path, (object)[
                    'fredkiusername' => $auth->fredkiusername,
                    'tokenexpires' => !empty($auth->tokenexpires) ? transform::datetime($auth->tokenexpires) : null,
                    'lastused' => !empty($auth->lastused) ? transform::datetime($auth->lastused) : null,
                    'timecreated' => transform::datetime($auth->timecreated),
                    'timemodified' => transform::datetime($auth->timemodified),
                ]);
            }
            $maps = $DB->get_records('fredkisync_map', ['activityid' => $cm->instance, 'syncuserid' => $userid], 'filename ASC');
            if ($maps) {
                $files = [];
                foreach ($maps as $map) {
                    $files[] = (object)[
                        'filename' => $map->filename,
                        'lastattempt' => !empty($map->lastattempt) ? transform::datetime($map->lastattempt) : null,
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:synchronisations', 'mod_fredkisync')],
                    (object)['files' => $files]
                );
            }
        }
    }

    /**
     * Deletes all local personal data in a module context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context instanceof \context_module) {
            $cm = get_coursemodule_from_id('fredkisync', $context->instanceid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $DB->delete_records('fredkisync_userauth', ['fredkisyncid' => $cm->instance]);
                $DB->set_field('fredkisync_map', 'syncuserid', 0, ['activityid' => $cm->instance]);
            }
        }
    }

    /**
     * Deletes a user's local personal data.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('fredkisync', $context->instanceid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $DB->delete_records('fredkisync_userauth', [
                    'fredkisyncid' => $cm->instance,
                    'userid' => $contextlist->get_user()->id,
                ]);
                $DB->set_field('fredkisync_map', 'syncuserid', 0, [
                    'activityid' => $cm->instance,
                    'syncuserid' => $contextlist->get_user()->id,
                ]);
            }
        }
    }

    /**
     * Deletes local personal data for an approved set of users.
     *
     * @param approved_userlist $userlist Approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('fredkisync', $context->instanceid, 0, false, IGNORE_MISSING);
        if ($cm && $userlist->get_userids()) {
            [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $params['activityid'] = $cm->instance;
            $DB->delete_records_select('fredkisync_userauth', "fredkisyncid = :activityid AND userid $insql", $params);
            $DB->set_field_select('fredkisync_map', 'syncuserid', 0, "activityid = :activityid AND syncuserid $insql", $params);
        }
    }
}
