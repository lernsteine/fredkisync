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
 * Activity configuration form for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Defines the FredKI® Sync activity form.
 */
class mod_fredkisync_mod_form extends moodleform_mod {
    /**
     * Defines form elements.
     */
    public function definition(): void {
        global $PAGE;

        $mform = $this->_form;
        $current = $this->current ?? new stdClass();
        $locked = !empty($current->knowledgeid);
        $defaultworkspace = get_config('mod_fredkisync', 'workspace') ?: 'default';

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('hidden', 'workspace', $current->workspace ?? $defaultworkspace);
        $mform->setType('workspace', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'syncenabled', 1);
        $mform->setType('syncenabled', PARAM_BOOL);
        $mform->addElement('header', 'fredkisettingsheader', get_string('fredkisettingsheader', 'mod_fredkisync'));

        if ($locked) {
            $mform->addElement('static', 'lockedinfo', '', get_string('settingslocked', 'mod_fredkisync'));
            $mform->addElement(
                'static',
                'lockedknowledgeid',
                get_string('knowledgebaseid', 'mod_fredkisync'),
                s($current->knowledgeid)
            );
            $mform->addElement(
                'static',
                'lockedknowledgebasename',
                get_string('knowledgebasename', 'mod_fredkisync'),
                s($current->knowledgebasename ?? $current->knowledgeid)
            );
            $mform->addElement('hidden', 'knowledgeid', $current->knowledgeid);
            $mform->setType('knowledgeid', PARAM_TEXT);
            $mform->addElement('hidden', 'knowledgebasename', $current->knowledgebasename ?? '');
            $mform->setType('knowledgebasename', PARAM_TEXT);
        } else {
            $mform->addElement('static', 'fredkiintro', '', get_string('credentialsintro', 'mod_fredkisync'));
            $mform->addElement('text', 'fredkiusername', get_string('fredkiusername', 'mod_fredkisync'), [
                'size' => 40,
                'autocomplete' => 'username',
                'autocapitalize' => 'none',
                'spellcheck' => 'false',
            ]);
            $mform->setType('fredkiusername', PARAM_TEXT);
            $mform->addRule('fredkiusername', null, 'required', null, 'client');
            $mform->addElement('passwordunmask', 'fredkipassword', get_string('fredkipassword', 'mod_fredkisync'), [
                'size' => 40,
                'autocomplete' => 'current-password',
            ]);
            $mform->setType('fredkipassword', PARAM_RAW_TRIMMED);
            $mform->addRule('fredkipassword', null, 'required', null, 'client');

            $buttonid = 'id_fredki_load_knowledgebases';
            $statusid = 'fredki-knowledgebase-status';
            $mform->addElement('html', html_writer::div(
                html_writer::tag('button', get_string('loadknowledgebases', 'mod_fredkisync'), [
                    'type' => 'button',
                    'class' => 'btn btn-primary',
                    'id' => $buttonid,
                ]) . html_writer::div('', 'mt-2', ['id' => $statusid]),
                'mb-3'
            ));

            // The AJAX endpoint stores the returned list in the Moodle session. On the
            // subsequent form submission these options are therefore already known to
            // MoodleQuickForm and the submitted value passes select validation.
            global $SESSION;
            $sessionitems = $SESSION->fredkisync_knowledgebases[$this->_course->id] ?? [];
            $knowledgeoptions = ['' => get_string('chooseknowledgebase', 'mod_fredkisync')];
            foreach ($sessionitems as $knowledgeid => $knowledgename) {
                $knowledgeoptions[(string)$knowledgeid] = (string)$knowledgename;
            }
            $mform->addElement('select', 'knowledgeid',
                get_string('availableknowledgebases', 'mod_fredkisync'), $knowledgeoptions);
            $mform->setType('knowledgeid', PARAM_TEXT);
            $mform->addElement('hidden', 'knowledgebasename', '');
            $mform->setType('knowledgebasename', PARAM_TEXT);

            $PAGE->requires->js_call_amd('mod_fredkisync/knowledgebases', 'init', [[
                'courseId' => (int)$this->_course->id,
                'buttonId' => $buttonid,
                'statusId' => $statusid,
                'selectId' => 'id_knowledgeid',
                'usernameId' => 'id_fredkiusername',
                'passwordId' => 'id_fredkipassword',
                'workspaceId' => 'id_workspace',
                'nameId' => 'id_knowledgebasename',
                'strings' => [
                    'entercredentials' => get_string('entercredentials', 'mod_fredkisync'),
                    'loading' => get_string('loadingknowledgebases', 'mod_fredkisync'),
                    'loaded' => get_string('knowledgebasesloaded', 'mod_fredkisync', '{$a}'),
                    'none' => get_string('noknowledgebases', 'mod_fredkisync'),
                    'loadfailed' => get_string('knowledgebasesloadfailed', 'mod_fredkisync'),
                    'choose' => get_string('chooseknowledgebase', 'mod_fredkisync'),
                ],
            ]]);
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Validates the form.
     *
     * @param array $data Submitted values.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($this->current->knowledgeid) && empty($data['knowledgeid'])) {
            $errors['knowledgeid'] = get_string('knowledgebaserequired', 'mod_fredkisync');
        }
        return $errors;
    }

    /**
     * Prepares default form values.
     *
     * @param array $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        $defaultvalues['workspace'] = $defaultvalues['workspace'] ?? (get_config('mod_fredkisync', 'workspace') ?: 'default');
        $defaultvalues['syncenabled'] = $defaultvalues['syncenabled'] ?? 1;
    }
}
