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
 * Synchronisation service for FredKI® documents.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\local;

/**
 * Synchronises individual file mapping records.
 */
class manual_sync_service {
    /** Maximum number of automatic attempts. */
    public const MAX_ATTEMPTS = 5;

    /**
     * Builds a FredKI® client for one explicit Moodle user and activity.
     *
     * Cron must never fall back to another user's token. A configured system
     * account is intentionally not used for user-initiated synchronisation.
     *
     * @param \stdClass $activity Activity record.
     * @param int $userid Moodle user ID that queued the operation.
     * @return fredki_client
     */
    protected static function get_client(\stdClass $activity, int $userid): fredki_client {
        $config = get_config('mod_fredkisync');
        $workspace = !empty($activity->workspace) ? $activity->workspace : ($config->workspace ?? 'default');
        $userauth = token_manager::get((int)$activity->id, $userid);

        if (!$userauth || empty($userauth->token)) {
            throw new api_exception(false, 401);
        }

        $client = new fredki_client(
            $config->baseurl ?? '',
            $workspace,
            $userauth->fredkiusername ?? '',
            '',
            $config->loginpath ?? '/admin/api/user/login',
            !empty($config->debuglog)
        );
        $client->set_token((string)$userauth->token);
        return $client;
    }

    /**
     * Synchronises a pending mapping.
     *
     * @param \stdClass $activity Activity record.
     * @param \stdClass $maprecord Mapping record.
     * @param int $userid Moodle user ID whose token must be used.
     * @throws api_exception When retry handling is required.
     */
    public static function sync_record(\stdClass $activity, \stdClass $maprecord, int $userid): void {
        global $DB;

        if ($maprecord->status !== 'pending') {
            return;
        }

        $fs = get_file_storage();
        $file = $fs->get_file_by_hash($maprecord->pathnamehash);
        if (!$file || $file->is_directory()) {
            self::mark_permanent_error($maprecord, 'filenotfound');
            return;
        }

        $tempdir = make_request_directory();
        $temppath = $tempdir . '/' . $file->get_filename();
        $file->copy_content_to($temppath);
        $sourceurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);

        try {
            $course = get_course($activity->course);
            $coursename = format_string($course->fullname);
        } catch (\Throwable $e) {
            $coursename = '';
        }

        try {
            $client = self::get_client($activity, $userid);
            $manager = new sync_manager($client);
            $result = $manager->sync_new_or_changed_file(
                $activity->knowledgeid,
                $temppath,
                $file->get_filename(),
                !empty($maprecord->fredki_document_id) ? $maprecord->fredki_document_id : null,
                $sourceurl,
                $coursename,
                format_string($activity->name ?? '')
            );
            $maprecord->fredki_document_id = $result['document_id'] ?? null;
            $maprecord->fredki_source_file_id = $result['source_file_id'] ?? null;
            $maprecord->status = 'synced';
            $maprecord->lasterror = null;
            $maprecord->attempts = 0;
            $maprecord->lastattempt = time();
            $maprecord->nextruntime = 0;
            $maprecord->timesynced = time();
            $maprecord->timemodified = time();
            $DB->update_record('fredkisync_map', $maprecord);
        } catch (api_exception $e) {
            self::handle_api_failure($maprecord, $e, $userid);
        } catch (\Throwable $e) {
            self::mark_permanent_error($maprecord, 'syncgenericerror');
        } finally {
            if (file_exists($temppath)) {
                unlink($temppath);
            }
        }
    }

    /**
     * Deletes one remote document for a deleted mapping.
     *
     * @param \stdClass $activity Activity record.
     * @param \stdClass $maprecord Mapping record.
     * @param int $userid Moodle user ID whose token must be used.
     * @throws api_exception When retry handling is required.
     */
    public static function delete_record(\stdClass $activity, \stdClass $maprecord, int $userid): void {
        global $DB;

        if ($maprecord->status !== 'deleted') {
            return;
        }
        if (empty($maprecord->fredki_document_id)) {
            $DB->delete_records('fredkisync_map', ['id' => $maprecord->id]);
            return;
        }

        try {
            $client = self::get_client($activity, $userid);
            $client->delete_document($activity->knowledgeid, $maprecord->fredki_document_id);
            $DB->delete_records('fredkisync_map', ['id' => $maprecord->id]);
        } catch (api_exception $e) {
            if ($e->get_http_code() === 404) {
                // Already removed in FredKI®.
                $DB->delete_records('fredkisync_map', ['id' => $maprecord->id]);
                return;
            }
            self::handle_api_failure($maprecord, $e, $userid);
        } catch (\Throwable $e) {
            self::mark_permanent_error($maprecord, 'syncgenericerror');
        }
    }

    /**
     * Applies retry or permanent-failure state.
     *
     * @param \stdClass $maprecord Mapping record.
     * @param api_exception $exception API exception.
     * @param int $userid User ID retained for diagnostics and retries.
     * @throws api_exception For a temporary failure that should be requeued.
     */
    private static function handle_api_failure(
        \stdClass $maprecord,
        api_exception $exception,
        int $userid
    ): void {
        global $DB;

        $attempts = (int)($maprecord->attempts ?? 0) + 1;
        $maprecord->attempts = $attempts;
        $maprecord->lastattempt = time();
        $maprecord->syncuserid = $userid;
        $maprecord->timemodified = time();

        if ($exception->is_transient() && $attempts < self::MAX_ATTEMPTS) {
            $maprecord->status = $maprecord->status === 'deleted' ? 'deleted' : 'pending';
            $maprecord->nextruntime = time() + self::retry_delay($attempts);
            $maprecord->lasterror = get_string('syncretrypending', 'mod_fredkisync', $attempts);
            $DB->update_record('fredkisync_map', $maprecord);
            throw $exception;
        }

        $maprecord->status = 'error';
        $maprecord->nextruntime = 0;
        $maprecord->lasterror = $exception->get_http_code() === 401 || $exception->get_http_code() === 403
            ? get_string('tokeninvalid', 'mod_fredkisync')
            : get_string('syncgenericerror', 'mod_fredkisync');
        $DB->update_record('fredkisync_map', $maprecord);
    }

    /**
     * Marks a non-retryable mapping error.
     *
     * @param \stdClass $maprecord Mapping record.
     * @param string $stringkey Language string key.
     */
    private static function mark_permanent_error(\stdClass $maprecord, string $stringkey): void {
        global $DB;

        $maprecord->status = 'error';
        $maprecord->attempts = (int)($maprecord->attempts ?? 0) + 1;
        $maprecord->lastattempt = time();
        $maprecord->nextruntime = 0;
        $maprecord->lasterror = get_string($stringkey, 'mod_fredkisync');
        $maprecord->timemodified = time();
        $DB->update_record('fredkisync_map', $maprecord);
    }

    /**
     * Returns an exponential retry delay capped at one hour.
     *
     * @param int $attempt Attempt number.
     * @return int Delay in seconds.
     */
    public static function retry_delay(int $attempt): int {
        return min(3600, 60 * (2 ** max(0, $attempt - 1)));
    }
}
