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
import {getString} from 'core/str';

/**
 * Initialise the knowledge-base selector.
 *
 * @param {Object} config Element IDs and course context.
 */
export const init = (config) => {
    const button = document.getElementById(config.buttonId);
    const status = document.getElementById(config.statusId);
    const select = document.getElementById(config.selectId);
    const knowledgeId = document.getElementById(config.knowledgeId);
    const username = document.getElementById(config.usernameId);
    const password = document.getElementById(config.passwordId);
    const workspace = document.getElementById(config.workspaceId);
    const selectedName = document.getElementById(config.nameId);

    if (!button || !select) {
        return;
    }

    const setStatus = (message, className = '') => {
        if (status) {
            status.className = className;
            status.textContent = message;
        }
    };

    const reset = async() => {
        while (select.firstChild) {
            select.removeChild(select.firstChild);
        }
        const option = document.createElement('option');
        option.value = '';
        option.textContent = await getString('chooseknowledgebase', 'mod_fredkisync');
        select.append(option);
        if (knowledgeId) {
            knowledgeId.value = '';
        }
        if (selectedName) {
            selectedName.value = '';
        }
    };

    const updateHiddenField = (field, value) => {
        if (!field) {
            return;
        }
        field.value = value;
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
    };

    const syncSelection = () => {
        const option = select.options[select.selectedIndex];
        const value = option && option.value ? option.value : '';
        updateHiddenField(knowledgeId, value);
        updateHiddenField(selectedName, value && option ? option.textContent.trim() : '');
    };

    select.addEventListener('change', syncSelection);
    const form = select.closest('form');
    if (form) {
        form.addEventListener('submit', syncSelection);
    }

    button.addEventListener('click', async() => {
        const previousValue = knowledgeId?.value || select.value || '';
        const userValue = username?.value.trim() ?? '';
        const passwordValue = password?.value ?? '';
        if (!userValue || !passwordValue) {
            setStatus(await getString('entercredentials', 'mod_fredkisync'), 'alert alert-warning');
            return;
        }

        button.disabled = true;
        await reset();
        setStatus(await getString('loadingknowledgebases', 'mod_fredkisync'), 'alert alert-info');

        try {
            const response = await Ajax.call([{
                methodname: 'mod_fredkisync_get_knowledgebases',
                args: {
                    courseid: config.courseId,
                    username: userValue,
                    password: passwordValue,
                    workspace: workspace?.value || 'default',
                },
            }])[0];

            const items = Array.isArray(response.items) ? response.items : [];
            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                select.append(option);
            });
            if (previousValue && items.some((item) => String(item.id) === String(previousValue))) {
                select.value = previousValue;
                syncSelection();
            }
            if (items.length === 0) {
                setStatus(await getString('noknowledgebases', 'mod_fredkisync'), 'alert alert-warning');
                return;
            }
            const message = await getString('knowledgebasesloaded', 'mod_fredkisync', items.length);
            setStatus(message, 'alert alert-success');
        } catch (error) {
            setStatus(await getString('knowledgebasesloadfailed', 'mod_fredkisync'), 'alert alert-danger');
        } finally {
            button.disabled = false;
        }
    });
};
