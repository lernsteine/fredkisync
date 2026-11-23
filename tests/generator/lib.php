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
 * Data generator for FredKI® Sync tests.
 *
 * @package    mod_fredkisync
 * @category   test
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_fredkisync_generator extends testing_module_generator {
    /**
     * Creates a FredKI® Sync activity instance.
     *
     * @param array|stdClass $record Instance data.
     * @param array|null $options Course module options.
     * @return stdClass Activity record with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;
        $record->knowledgeid = $record->knowledgeid ?? 'test-knowledge';
        $record->knowledgebasename = $record->knowledgebasename ?? 'Test knowledge';
        $record->workspace = $record->workspace ?? 'default';
        $record->syncenabled = $record->syncenabled ?? 1;
        return parent::create_instance($record, (array)$options);
    }
}
