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
 * Loads the FredKI® knowledge bases into the activity settings form.
 *
 * @module     mod_fredkisync/knowledgebases
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Initialise the knowledge-base selector.
 *
 * The click handler is delegated to the document, so it also works if the
 * form is re-rendered. All texts are passed in from PHP, so no further string
 * requests are needed before the user sees feedback.
 *
 * @param {Object} config Element IDs, course ID and texts.
 */
export const init = (config) => {
    const byId = (id) => (id ? document.getElementById(id) : null);
    const texts = config.strings || {};

    const setStatus = (message, type) => {
        const status = byId(config.statusId);
        if (status) {
            status.className = 'mt-2 alert alert-' + type;
            status.textContent = message;
        }
    };

    const getSelect = () => byId(config.selectId) || document.querySelector('select[name="knowledgeid"]');
    const getNameField = () => byId(config.nameId) || document.querySelector('input[name="knowledgebasename"]');

    const syncSelection = () => {
        const select = getSelect();
        const nameField = getNameField();
        if (!select || !nameField) {
            return;
        }
        const option = select.options[select.selectedIndex];
        nameField.value = option && option.value ? option.textContent.trim() : '';
    };

    const load = async(button) => {
        const select = getSelect();
        const username = byId(config.usernameId) || document.querySelector('input[name="fredkiusername"]');
        const password = byId(config.passwordId) || document.querySelector('input[name="fredkipassword"]');
        const workspace = byId(config.workspaceId) || document.querySelector('input[name="workspace"]');
        const userValue = username ? username.value.trim() : '';
        const passwordValue = password ? password.value : '';

        if (!userValue || !passwordValue) {
            setStatus(texts.entercredentials, 'warning');
            return;
        }
        if (!select) {
            setStatus(texts.loadfailed, 'danger');
            return;
        }

        const previousValue = select.value;
        button.disabled = true;
        setStatus(texts.loading, 'info');
        try {
            const response = await Ajax.call([{
                methodname: 'mod_fredkisync_get_knowledgebases',
                args: {
                    courseid: config.courseId,
                    username: userValue,
                    password: passwordValue,
                    workspace: (workspace && workspace.value) || 'default',
                },
            }])[0];
            const items = Array.isArray(response.items) ? response.items : [];

            while (select.options.length > 0) {
                select.remove(0);
            }
            select.add(new Option(texts.choose, ''));
            items.forEach((item) => select.add(new Option(item.name, item.id)));
            if (previousValue && items.some((item) => String(item.id) === String(previousValue))) {
                select.value = previousValue;
            }
            syncSelection();

            if (items.length === 0) {
                setStatus(texts.none, 'warning');
            } else {
                setStatus(String(texts.loaded).replace('{$a}', items.length), 'success');
            }
        } catch (error) {
            const detail = error && (error.message || error.error) ? ' (' + (error.message || error.error) + ')' : '';
            setStatus(texts.loadfailed + detail, 'danger');
        } finally {
            button.disabled = false;
        }
    };

    document.addEventListener('click', (e) => {
        const button = e.target.closest('#' + config.buttonId);
        if (button) {
            e.preventDefault();
            load(button);
        }
    });
    document.addEventListener('change', (e) => {
        if (e.target.matches('select[name="knowledgeid"]')) {
            syncSelection();
        }
    });
    document.addEventListener('submit', syncSelection, true);
};
