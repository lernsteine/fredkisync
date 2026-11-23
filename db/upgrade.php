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
 * Database upgrade steps for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrades the FredKI® Sync database schema.
 *
 * @param int $oldversion Installed version.
 * @return bool
 */
function xmldb_fredkisync_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2026072901) {
        $table = new xmldb_table('fredkisync');
        foreach (['fredkiusername', 'fredkipassword'] as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        $authtable = new xmldb_table('fredkisync_userauth');
        $expires = new xmldb_field('tokenexpires', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'token');
        if (!$dbman->field_exists($authtable, $expires)) {
            $dbman->add_field($authtable, $expires);
        }
        $lastused = new xmldb_field('lastused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'tokenexpires');
        if (!$dbman->field_exists($authtable, $lastused)) {
            $dbman->add_field($authtable, $lastused);
        }
        upgrade_mod_savepoint(true, 2026072901, 'fredkisync');
    }

    if ($oldversion < 2026072902) {
        $table = new xmldb_table('fredkisync_map');
        $fields = [
            new xmldb_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lasterror'),
            new xmldb_field('lastattempt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'attempts'),
            new xmldb_field('nextruntime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'lastattempt'),
            new xmldb_field('syncuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'nextruntime'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        $index = new xmldb_index('statusruntime_ix', XMLDB_INDEX_NOTUNIQUE, ['status', 'nextruntime']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2026072902, 'fredkisync');
    }

    if ($oldversion < 2026072903) {
        $maptable = new xmldb_table('fredkisync_map');
        $pathnameindex = new xmldb_index(
            'pathnamehash_uix',
            XMLDB_INDEX_UNIQUE,
            ['activityid', 'pathnamehash']
        );

        // pathnamehash is part of a unique index. XMLDB requires the index to be
        // removed before changing the field default and restored afterwards.
        if ($dbman->index_exists($maptable, $pathnameindex)) {
            $dbman->drop_index($maptable, $pathnameindex);
        }

        $fields = [
            ['fredkisync', new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'course')],
            ['fredkisync', new xmldb_field('knowledgeid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, 'introformat')],
            [
                'fredkisync_map',
                new xmldb_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'activityid'),
            ],
            [
                'fredkisync_map',
                new xmldb_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'pathnamehash'),
            ],
            ['fredkisync_map', new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null, 'filename')],
        ];

        foreach ($fields as [$tablename, $field]) {
            $table = new xmldb_table($tablename);
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_default($table, $field);
            }
        }

        if (!$dbman->index_exists($maptable, $pathnameindex)) {
            $dbman->add_index($maptable, $pathnameindex);
        }

        upgrade_mod_savepoint(true, 2026072903, 'fredkisync');
    }

    if ($oldversion < 2026072904) {
        upgrade_mod_savepoint(true, 2026072904, 'fredkisync');
    }

    if ($oldversion < 2026112300) {
        // Encrypt access tokens that were stored in plain text by earlier versions.
        $rs = $DB->get_recordset('fredkisync_userauth', null, '', 'id, token');
        foreach ($rs as $record) {
            $token = (string)$record->token;
            if ($token !== '' && !\mod_fredkisync\local\token_manager::is_encrypted($token)) {
                $DB->set_field(
                    'fredkisync_userauth',
                    'token',
                    \mod_fredkisync\local\token_manager::encrypt_token($token),
                    ['id' => $record->id]
                );
            }
        }
        $rs->close();

        upgrade_mod_savepoint(true, 2026112300, 'fredkisync');
    }

    return true;
}
