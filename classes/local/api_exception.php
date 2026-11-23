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
 * FredKI® API exception.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync\local;

/**
 * Describes a sanitised FredKI® API failure and whether it can be retried.
 */
class api_exception extends \moodle_exception {
    /** @var bool Whether retrying the request may succeed. */
    private bool $transient;

    /** @var int HTTP status code, or zero for a transport failure. */
    private int $httpcode;

    /**
     * Constructor.
     *
     * @param bool $transient Whether the failure is temporary.
     * @param int $httpcode HTTP status code, or zero.
     */
    public function __construct(bool $transient, int $httpcode = 0) {
        parent::__construct('fredkiapifailed', 'mod_fredkisync');
        $this->transient = $transient;
        $this->httpcode = $httpcode;
    }

    /**
     * Returns whether the operation may be retried.
     *
     * @return bool
     */
    public function is_transient(): bool {
        return $this->transient;
    }

    /**
     * Returns the HTTP status code.
     *
     * @return int
     */
    public function get_http_code(): int {
        return $this->httpcode;
    }
}
