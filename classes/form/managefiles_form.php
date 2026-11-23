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
 * File management form for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync\form;

use moodleform;

/**
 * File manager form.
 */
class managefiles_form extends moodleform {
    /** Defines form fields. */
    public function definition(): void {
        $mform = $this->_form;
        $options = $this->_customdata['fileoptions'];
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('filemanager', 'files_filemanager', get_string('files', 'mod_fredkisync'), null, $options);
        $mform->addElement('static', 'supportedfiletypes', '', get_string('supportedfiletypes', 'mod_fredkisync'));
        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
