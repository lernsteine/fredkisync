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
 * Shared helpers for the plugin JSON endpoints.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\local;

/**
 * Shared endpoint authorisation and response handling.
 */
final class ajax_helper {
    /**
     * Validates the request and returns the course context.
     *
     * @return \context_course
     */
    public static function require_course_access(): \context_course {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new \moodle_exception('invalidrequestmethod', 'mod_fredkisync');
        }

        $courseid = required_param('courseid', PARAM_INT);
        $course = get_course($courseid);
        require_login($course);
        require_sesskey();

        $context = \context_course::instance($course->id);
        require_capability('mod/fredkisync:addinstance', $context);

        return $context;
    }

    /**
     * Sends a JSON response and stops script execution.
     *
     * @param array $payload Response data.
     * @param int $statuscode HTTP status code.
     */
    public static function send_json(array $payload, int $statuscode = 200): void {
        http_response_code($statuscode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
