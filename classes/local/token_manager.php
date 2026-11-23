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
 * Manages the lifecycle of FredKI® access tokens.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\local;

/**
 * Stores, retrieves and revokes activity-scoped FredKI® tokens.
 *
 * Tokens are encrypted at rest with Moodle's core encryption API.
 */
class token_manager {
    /**
     * Encrypts a token for storage.
     *
     * @param string $token Plain access token.
     * @return string Encrypted token.
     */
    public static function encrypt_token(string $token): string {
        return \core\encryption::encrypt($token);
    }

    /**
     * Decrypts a stored token. Tokens stored by older plugin versions are returned unchanged.
     *
     * @param string $stored Stored value.
     * @return string Plain access token, or an empty string if it cannot be decrypted.
     */
    public static function decrypt_token(string $stored): string {
        if ($stored === '' || !self::is_encrypted($stored)) {
            return $stored;
        }
        try {
            return \core\encryption::decrypt($stored);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Returns whether a stored value was produced by the core encryption API.
     *
     * @param string $stored Stored value.
     * @return bool
     */
    public static function is_encrypted(string $stored): bool {
        return strpos($stored, \core\encryption::METHOD_SODIUM . ':') === 0;
    }

    /**
     * Stores a token for a user and activity.
     *
     * @param int $activityid Activity ID.
     * @param int $userid User ID.
     * @param string $username FredKI® username.
     * @param string $token Access token.
     */
    public static function store(int $activityid, int $userid, string $username, string $token): void {
        global $DB;

        $now = time();
        $lifetime = max(0, (int)get_config('mod_fredkisync', 'tokenlifetime'));
        $record = $DB->get_record('fredkisync_userauth', [
            'userid' => $userid,
            'fredkisyncid' => $activityid,
        ]);
        $values = (object)[
            'userid' => $userid,
            'fredkisyncid' => $activityid,
            'fredkiusername' => $username,
            'token' => self::encrypt_token($token),
            'tokenexpires' => $lifetime > 0 ? $now + $lifetime : 0,
            'lastused' => $now,
            'timemodified' => $now,
        ];
        if ($record) {
            $values->id = $record->id;
            $DB->update_record('fredkisync_userauth', $values);
        } else {
            $values->timecreated = $now;
            $DB->insert_record('fredkisync_userauth', $values);
        }
    }

    /**
     * Returns the usable token record of exactly this user and activity.
     *
     * There is deliberately no fallback to another user's token.
     *
     * @param int $activityid Activity ID.
     * @param int $userid Moodle user ID.
     * @return \stdClass|null Record with the decrypted token, or null.
     */
    public static function get(int $activityid, int $userid): ?\stdClass {
        global $DB;

        $record = $DB->get_record('fredkisync_userauth', ['fredkisyncid' => $activityid, 'userid' => $userid]);
        if (!$record) {
            return null;
        }
        if (!empty($record->tokenexpires) && $record->tokenexpires <= time()) {
            $DB->delete_records('fredkisync_userauth', ['id' => $record->id]);
            return null;
        }
        $record->lastused = time();
        $DB->set_field('fredkisync_userauth', 'lastused', $record->lastused, ['id' => $record->id]);
        $record->token = self::decrypt_token((string)$record->token);
        return $record;
    }

    /**
     * Revokes all local tokens for an activity.
     *
     * @param int $activityid Activity ID.
     */
    public static function revoke_activity(int $activityid): void {
        global $DB;
        $DB->delete_records('fredkisync_userauth', ['fredkisyncid' => $activityid]);
    }

    /**
     * Removes expired tokens.
     */
    public static function purge_expired(): void {
        global $DB;
        $DB->delete_records_select('fredkisync_userauth', 'tokenexpires > 0 AND tokenexpires <= :now', ['now' => time()]);
    }
}
