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
 * Administration settings for the FredKI® Sync activity module.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Where to get a FredKI server: hosted by lernsteine.com or self-hosted from GitHub.
    $offerlinks = html_writer::link(
        'https://fredki.com/fredki_moodle_hosting/',
        get_string('getfredkihosted', 'mod_fredkisync'),
        ['class' => 'btn btn-primary me-2 mb-2', 'target' => '_blank', 'rel' => 'noopener']
    ) . html_writer::link(
        'https://github.com/lernsteine/FredKI',
        get_string('getfredkiselfhost', 'mod_fredkisync'),
        ['class' => 'btn btn-outline-secondary mb-2', 'target' => '_blank', 'rel' => 'noopener']
    );
    $settings->add(new admin_setting_heading(
        'mod_fredkisync/getfredki',
        get_string('getfredki', 'mod_fredkisync'),
        html_writer::tag('p', get_string('getfredki_desc', 'mod_fredkisync')) . html_writer::div($offerlinks)
    ));

    $settings->add(new admin_setting_configtext(
        'mod_fredkisync/baseurl',
        get_string('baseurl', 'mod_fredkisync'),
        get_string('baseurl_desc', 'mod_fredkisync'),
        '',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configtext(
        'mod_fredkisync/workspace',
        get_string('defaultworkspace', 'mod_fredkisync'),
        get_string('defaultworkspace_desc', 'mod_fredkisync'),
        'default',
        PARAM_ALPHANUMEXT
    ));
    $settings->add(new admin_setting_configtext(
        'mod_fredkisync/loginpath',
        get_string('loginpath', 'mod_fredkisync'),
        get_string('loginpath_desc', 'mod_fredkisync'),
        '/admin/api/user/login',
        PARAM_PATH
    ));
    $settings->add(new admin_setting_configduration(
        'mod_fredkisync/tokenlifetime',
        get_string('tokenlifetime', 'mod_fredkisync'),
        get_string('tokenlifetime_desc', 'mod_fredkisync'),
        WEEKSECS
    ));
    $settings->add(new admin_setting_configcheckbox(
        'mod_fredkisync/debuglog',
        get_string('debuglog', 'mod_fredkisync'),
        get_string('debuglog_desc', 'mod_fredkisync'),
        0
    ));
}
