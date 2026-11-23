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
 * Document synchronisation manager for FredKI® Sync.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_fredkisync\local;

/**
 * Uploads, replaces and annotates documents in a FredKI® knowledge base.
 */
class sync_manager {
    /** @var fredki_client API client. */
    protected $client;

    /**
     * Constructor.
     *
     * @param fredki_client $client Authenticated API client.
     */
    public function __construct(fredki_client $client) {
        $this->client = $client;
    }

    /**
     * Uploads a new or changed file, replacing the previous document if there is one.
     *
     * @param string $knowledgeid Knowledge-base ID.
     * @param string $filepath Local path of a temporary copy.
     * @param string $filename Original filename.
     * @param string|null $olddocumentid Previous FredKI® document ID.
     * @param string|null $sourceurl Moodle URL of the file.
     * @param string|null $coursename Course name.
     * @param string|null $activityname Activity name.
     * @return array Document ID, source file ID and name.
     */
    public function sync_new_or_changed_file(
        string $knowledgeid,
        string $filepath,
        string $filename,
        ?string $olddocumentid = null,
        ?string $sourceurl = null,
        ?string $coursename = null,
        ?string $activityname = null
    ): array {
        $splititem = $this->client->split_upload($knowledgeid, $filepath, $filename);
        $paragraphs = $splititem['content'] ?? [];

        // Keep PDF/Office files as native FredKI® imports, but prepend one small Moodle
        // context paragraph. This preserves the complete FredKI® split result while
        // telling the agent where and how the file is used in Moodle.
        if ($this->is_native_document_file($filename)) {
            $contextparagraph = $this->build_moodle_context_paragraph(
                $paragraphs,
                $filename,
                $sourceurl,
                $coursename,
                $activityname
            );
            $paragraphs = $this->prepend_context_paragraph($paragraphs, $contextparagraph);
        }

        $payload = [[
            'name' => $splititem['name'] ?? $filename,
            'paragraphs' => $paragraphs,
            'source_file_id' => $splititem['source_file_id'] ?? '',
        ]];
        $create = $this->client->batch_create($knowledgeid, $payload);
        $document = $create['data'][0] ?? null;
        if (!$document || empty($document['id'])) {
            throw new \moodle_exception('fredkiapifailed', 'mod_fredkisync');
        }

        // Remove the previous version only after the new one exists, so a failed
        // upload never leaves the knowledge base without the document.
        if (!empty($olddocumentid) && (string)$olddocumentid !== (string)$document['id']) {
            try {
                $this->client->delete_document($knowledgeid, $olddocumentid);
            } catch (api_exception $e) {
                // A document that no longer exists remotely needs no further action.
                if ($e->get_http_code() !== 404) {
                    throw $e;
                }
            }
        }
        return [
            'document_id' => $document['id'],
            'source_file_id' => $splititem['source_file_id'] ?? null,
            'name' => $splititem['name'] ?? $filename,
        ];
    }

    /**
     * Files that should stay as native FredKI® documents, with one Moodle context
     * paragraph prepended. The extracted content itself remains untouched.
     *
     * @param string $filename Filename.
     * @return bool
     */
    protected function is_native_document_file(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'], true);
    }

    /**
     * Builds a compact context paragraph for the agent. The real document content
     * follows as native FredKI® paragraphs returned by the split endpoint.
     *
     * @param mixed $originalparagraphs Paragraphs returned by the split endpoint.
     * @param string $filename Filename.
     * @param string|null $sourceurl Moodle URL of the file.
     * @param string|null $coursename Course name.
     * @param string|null $activityname Activity name.
     * @return array Paragraph list containing one context paragraph.
     */
    protected function build_moodle_context_paragraph(
        $originalparagraphs,
        string $filename,
        ?string $sourceurl,
        ?string $coursename,
        ?string $activityname
    ) {
        $title = trim(pathinfo($filename, PATHINFO_FILENAME));
        if ($title === '') {
            $title = $filename;
        }

        $lines = [
            get_string('documentcontextheading', 'mod_fredkisync'),
            '',
            get_string('documentcontexttitle', 'mod_fredkisync', $title),
            get_string('documentcontextfile', 'mod_fredkisync', $filename),
        ];

        $course = trim((string)$coursename);
        if ($course !== '') {
            $lines[] = get_string('documentcontextcourse', 'mod_fredkisync', $course);
        }

        $activity = trim((string)$activityname);
        if ($activity !== '') {
            $lines[] = get_string('documentcontextactivity', 'mod_fredkisync', $activity);
        }

        $source = trim((string)$sourceurl);
        if ($source !== '') {
            $lines[] = get_string('documentcontextsource', 'mod_fredkisync', $source);
        }

        $lines[] = '';
        $lines[] = get_string('documentcontextinstruction', 'mod_fredkisync');

        return $this->make_single_paragraph_like_original($originalparagraphs, implode("\n", $lines));
    }

    /**
     * Prepends the context paragraph to the split paragraphs.
     *
     * @param mixed $paragraphs Split paragraphs.
     * @param mixed $contextparagraphs Context paragraph(s).
     * @return array
     */
    protected function prepend_context_paragraph($paragraphs, $contextparagraphs) {
        if (empty($contextparagraphs)) {
            return $paragraphs;
        }
        if (!is_array($contextparagraphs)) {
            $contextparagraphs = [$contextparagraphs];
        }
        if (is_string($paragraphs)) {
            return array_merge($contextparagraphs, [$paragraphs]);
        }
        if (!is_array($paragraphs) || $paragraphs === []) {
            return $contextparagraphs;
        }

        foreach (['content', 'text', 'paragraph'] as $textkey) {
            if (array_key_exists($textkey, $paragraphs) && is_string($paragraphs[$textkey])) {
                return array_merge($contextparagraphs, [$paragraphs]);
            }
        }

        return array_merge($contextparagraphs, $paragraphs);
    }

    /**
     * Builds one paragraph while preserving the paragraph shape returned by split_upload.
     *
     * @param mixed $originalparagraphs Paragraphs returned by the split endpoint.
     * @param string $text Paragraph text.
     * @return array
     */
    protected function make_single_paragraph_like_original($originalparagraphs, string $text) {
        $text = trim($text);
        if ($text === '') {
            $text = get_string('documentcontextheading', 'mod_fredkisync');
        }

        if (is_string($originalparagraphs)) {
            return [$text];
        }

        if (!is_array($originalparagraphs) || $originalparagraphs === []) {
            return [$text];
        }

        foreach (['content', 'text', 'paragraph'] as $textkey) {
            if (array_key_exists($textkey, $originalparagraphs)) {
                $paragraph = $originalparagraphs;
                $paragraph[$textkey] = $text;
                return [$paragraph];
            }
        }

        $first = reset($originalparagraphs);
        if (is_string($first)) {
            return [$text];
        }
        if (is_array($first)) {
            $paragraph = $first;
            $updated = false;
            foreach (['content', 'text', 'paragraph'] as $textkey) {
                if (array_key_exists($textkey, $paragraph)) {
                    $paragraph[$textkey] = $text;
                    $updated = true;
                    break;
                }
            }
            if (!$updated) {
                $paragraph['content'] = $text;
            }
            return [$paragraph];
        }

        return [$text];
    }
}
