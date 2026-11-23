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
 * Returns the FredKI® knowledge bases for the activity settings form.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_fredkisync\local\ajax_helper;
use mod_fredkisync\local\fredki_client;

// The helper repeats the login, session key and capability checks for the course.
require_login(required_param('courseid', PARAM_INT));
ajax_helper::require_course_access();

try {
    $courseid = required_param('courseid', PARAM_INT);
    $username = required_param('username', PARAM_TEXT);
    $password = required_param('password', PARAM_RAW_TRIMMED);
    $workspace = optional_param('workspace', 'default', PARAM_ALPHANUMEXT);
    $config = get_config('mod_fredkisync');

    if (empty($config->baseurl)) {
        throw new moodle_exception('baseurlnotconfigured', 'mod_fredkisync');
    }

    $client = new fredki_client(
        $config->baseurl,
        $workspace ?: 'default',
        $username,
        $password,
        $config->loginpath ?? '/admin/api/user/login',
        !empty($config->debuglog)
    );
    $items = $client->list_knowledge_bases();

    // Remember the list in the session, so the select element accepts the chosen value on submit.
    $SESSION->fredkisync_knowledgebases[$courseid] = [];
    foreach ($items as $item) {
        $SESSION->fredkisync_knowledgebases[$courseid][(string)$item['id']] = (string)$item['name'];
    }

    ajax_helper::send_json([
        'success' => true,
        'items' => $items,
        'error' => '',
    ]);
} catch (\Throwable $exception) {
    debugging('[mod_fredkisync] Knowledge-base request failed: ' . get_class($exception), DEBUG_DEVELOPER);
    ajax_helper::send_json([
        'success' => false,
        'items' => [],
        'error' => get_string('fredkiapifailed', 'mod_fredkisync'),
    ], 400);
}
