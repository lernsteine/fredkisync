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
 * Privacy API tests for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync\privacy;

use core_privacy\local\request\approved_contextlist;

/**
 * Tests privacy deletion behaviour.
 *
 * @covers \mod_fredkisync\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * Tests deletion of a user's token and sync user reference.
     */
    public function test_delete_data_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('fredkisync', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $DB->insert_record('fredkisync_userauth', (object)[
            'userid' => $user->id, 'fredkisyncid' => $activity->id, 'fredkiusername' => 'test', 'token' => 'secret',
            'tokenexpires' => 0, 'lastused' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('fredkisync_map', (object)[
            'cmid' => $activity->cmid, 'activityid' => $activity->id, 'pathnamehash' => sha1('x'), 'filename' => 'a.pdf',
            'contenthash' => sha1('y'), 'status' => 'synced', 'syncuserid' => $user->id,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        provider::delete_data_for_user(new approved_contextlist($user, 'mod_fredkisync', [$context->id]));
        $this->assertFalse($DB->record_exists('fredkisync_userauth', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('fredkisync_map', ['syncuserid' => $user->id]));
        $this->assertTrue($DB->record_exists('fredkisync_map', ['activityid' => $activity->id]));
    }
}
