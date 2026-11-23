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
 * Tests for the FredKI® token manager.
 *
 * @package    mod_fredkisync
 * @category   test
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync;

use mod_fredkisync\local\token_manager;

/**
 * Tests token storage.
 *
 * @covers \mod_fredkisync\local\token_manager
 */
final class token_manager_test extends \advanced_testcase {
    /**
     * Tokens are encrypted at rest and only returned to their own user.
     */
    public function test_store_encrypts_and_scopes_token(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('fredkisync', ['course' => $course->id]);
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();

        token_manager::store($activity->id, $usera->id, 'alice', 'plain-token');

        $stored = $DB->get_field('fredkisync_userauth', 'token', ['userid' => $usera->id]);
        $this->assertNotSame('plain-token', $stored);
        $this->assertTrue(token_manager::is_encrypted($stored));
        $this->assertSame('plain-token', token_manager::get($activity->id, $usera->id)->token);
        $this->assertNull(token_manager::get($activity->id, $userb->id));
    }
}
