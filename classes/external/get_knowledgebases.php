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
 * External function for loading FredKI® knowledge bases.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_fredkisync\local\fredki_client;

/**
 * Loads the knowledge bases available to supplied FredKI® credentials.
 */
class get_knowledgebases extends external_api {
    /**
     * Defines accepted parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'username' => new external_value(PARAM_TEXT, 'FredKI username'),
            'password' => new external_value(PARAM_RAW_TRIMMED, 'FredKI password'),
            'workspace' => new external_value(PARAM_ALPHANUMEXT, 'FredKI workspace', VALUE_DEFAULT, 'default'),
        ]);
    }

    /**
     * Loads knowledge bases.
     *
     * @param int $courseid Course ID.
     * @param string $username FredKI® username.
     * @param string $password FredKI® password.
     * @param string $workspace FredKI® workspace.
     * @return array
     */
    public static function execute(int $courseid, string $username, string $password, string $workspace = 'default'): array {
        global $SESSION;
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'username' => $username,
            'password' => $password,
            'workspace' => $workspace,
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('mod/fredkisync:addinstance', $context);

        $config = get_config('mod_fredkisync');
        if (empty($config->baseurl)) {
            throw new \moodle_exception('baseurlnotconfigured', 'mod_fredkisync');
        }

        $client = new fredki_client(
            $config->baseurl,
            $params['workspace'],
            $params['username'],
            $params['password'],
            $config->loginpath ?? '/admin/api/user/login',
            !empty($config->debuglog)
        );

        $items = $client->list_knowledge_bases();
        $SESSION->fredkisync_knowledgebases[$params['courseid']] = [];
        foreach ($items as $item) {
            $SESSION->fredkisync_knowledgebases[$params['courseid']][(string)$item['id']] = (string)$item['name'];
        }

        return ['items' => $items];
    }

    /**
     * Defines returned data.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'items' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_TEXT, 'Knowledge base ID'),
                'name' => new external_value(PARAM_TEXT, 'Knowledge base name'),
            ])),
        ]);
    }
}
