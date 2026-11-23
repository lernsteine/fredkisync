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
 * Retry policy tests for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync;

use mod_fredkisync\local\manual_sync_service;

/**
 * Tests the deterministic retry policy.
 *
 * @covers \mod_fredkisync\local\manual_sync_service::retry_delay
 */
final class retry_test extends \advanced_testcase {
    /**
     * Retry delays increase exponentially and remain capped.
     */
    public function test_retry_delay(): void {
        $this->assertSame(60, manual_sync_service::retry_delay(1));
        $this->assertSame(120, manual_sync_service::retry_delay(2));
        $this->assertSame(240, manual_sync_service::retry_delay(3));
        $this->assertSame(3600, manual_sync_service::retry_delay(20));
    }
}
