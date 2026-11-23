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
 * HTTP client for the FredKI® API.
 *
 * @package    mod_fredkisync
 * @copyright  2026 Ralf Hagemeister, LERNSTEINE <https://lernsteine.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_fredkisync\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Minimal HTTP client for the FredKI® knowledge-base API.
 *
 * All requests use Moodle's curl wrapper, so the site's proxy and
 * curl security settings apply.
 */
class fredki_client {
    /** @var string Base URL without trailing slash. */
    protected string $baseurl;

    /** @var string FredKI® workspace. */
    protected string $workspace;

    /** @var string FredKI® username. */
    protected string $username;

    /** @var string FredKI® password, only used for login and never stored. */
    protected string $password;

    /** @var string Relative login path. */
    protected string $loginpath;

    /** @var bool Whether developer debugging output is enabled. */
    protected bool $debuglog;

    /** @var string|null Access token. */
    protected ?string $token = null;

    /**
     * Constructor.
     *
     * @param string $baseurl HTTPS base URL of the FredKI® service.
     * @param string $workspace FredKI® workspace.
     * @param string $username FredKI® username.
     * @param string $password FredKI® password (may be empty when a token is set).
     * @param string $loginpath Relative login path.
     * @param bool $debuglog Whether to write developer debugging output.
     */
    public function __construct(
        string $baseurl,
        string $workspace,
        string $username,
        string $password,
        string $loginpath = '/admin/api/user/login',
        bool $debuglog = false
    ) {
        $baseurl = rtrim($baseurl, '/');
        $parts = parse_url($baseurl);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new \moodle_exception('invalidbaseurl', 'mod_fredkisync');
        }
        $this->baseurl = $baseurl;
        $this->workspace = $workspace ?: 'default';
        $this->username = $username;
        $this->password = $password;
        $this->loginpath = $loginpath ?: '/admin/api/user/login';
        $this->debuglog = $debuglog;
    }

    /**
     * Sets an existing access token.
     *
     * @param string $token Access token.
     */
    public function set_token(string $token): void {
        $this->token = $token;
    }

    /**
     * Writes a developer debugging message if enabled. Never logs credentials.
     *
     * @param string $message Message.
     */
    protected function log_debug(string $message): void {
        if ($this->debuglog) {
            debugging('[mod_fredkisync] ' . $message, DEBUG_DEVELOPER);
        }
    }

    /**
     * Returns the workspace API base URL.
     *
     * @return string
     */
    protected function workspace_url(): string {
        return $this->baseurl . '/admin/api/workspace/' . rawurlencode($this->workspace);
    }

    /**
     * Logs in and returns the access token.
     *
     * @return string Access token.
     */
    public function login(): string {
        if ($this->token) {
            return $this->token;
        }
        $response = $this->request_json(
            'POST',
            $this->baseurl . '/' . ltrim($this->loginpath, '/'),
            ['username' => $this->username, 'password' => $this->password],
            false
        );
        $token = $response['data']['token'] ?? $response['token'] ?? '';
        if ($token === '') {
            throw new api_exception(false, 401);
        }
        $this->token = (string)$token;
        return $this->token;
    }

    /**
     * Lists the knowledge bases available to the authenticated account.
     *
     * @return array List of ['id' => string, 'name' => string].
     */
    public function list_knowledge_bases(): array {
        $this->login();
        $response = $this->request_json('GET', $this->workspace_url() . '/knowledge');
        $rows = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
        $result = [];
        foreach ($rows as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }
            $result[] = [
                'id' => (string)$item['id'],
                'name' => (string)($item['name'] ?? $item['title'] ?? $item['id']),
            ];
        }
        return $result;
    }

    /**
     * Uploads a file and lets FredKI® split it into paragraphs.
     *
     * @param string $knowledgeid Knowledge-base ID.
     * @param string $filepath Local path of a temporary copy.
     * @param string $filename Original filename.
     * @return array First split result.
     */
    public function split_upload(string $knowledgeid, string $filepath, string $filename): array {
        $this->login();
        $url = $this->workspace_url() . '/knowledge/' . rawurlencode($knowledgeid) . '/document/split';
        $curl = new \curl();
        $curl->setHeader(['Authorization: Bearer ' . $this->token, 'Accept: application/json']);
        $raw = $curl->post(
            $url,
            ['file' => new \CURLFile($filepath, 'application/octet-stream', $filename)],
            ['CURLOPT_TIMEOUT' => 120, 'CURLOPT_CONNECTTIMEOUT' => 20]
        );
        $this->assert_success($curl);
        $response = json_decode((string)$raw, true);
        if (!is_array($response) || empty($response['data'][0])) {
            throw new \moodle_exception('fredkiapifailed', 'mod_fredkisync');
        }
        return $response['data'][0];
    }

    /**
     * Creates documents from split paragraphs.
     *
     * @param string $knowledgeid Knowledge-base ID.
     * @param array $documents Document payload.
     * @return array Decoded response.
     */
    public function batch_create(string $knowledgeid, array $documents): array {
        return $this->request_json(
            'PUT',
            $this->workspace_url() . '/knowledge/' . rawurlencode($knowledgeid) . '/document/batch_create',
            $documents
        );
    }

    /**
     * Deletes a document from a knowledge base.
     *
     * @param string $knowledgeid Knowledge-base ID.
     * @param string $documentid Document ID.
     * @return array Decoded response.
     */
    public function delete_document(string $knowledgeid, string $documentid): array {
        return $this->request_json(
            'DELETE',
            $this->workspace_url() . '/knowledge/' . rawurlencode($knowledgeid) . '/document/' . rawurlencode($documentid)
        );
    }

    /**
     * Sends a JSON request.
     *
     * @param string $method HTTP method.
     * @param string $url Absolute URL.
     * @param array|null $payload JSON payload.
     * @param bool $withauth Whether to send the bearer token.
     * @return array Decoded response.
     */
    protected function request_json(string $method, string $url, ?array $payload = null, bool $withauth = true): array {
        if ($withauth && !$this->token) {
            $this->login();
        }
        $curl = new \curl();
        $headers = ['Accept: application/json'];
        if ($withauth) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $curl->setHeader($headers);
        $options = ['CURLOPT_TIMEOUT' => 60, 'CURLOPT_CONNECTTIMEOUT' => 20];
        $body = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        switch (strtoupper($method)) {
            case 'GET':
                $raw = $curl->get($url, [], $options);
                break;
            case 'POST':
                $raw = $curl->post($url, $body, $options);
                break;
            case 'PUT':
                $raw = $curl->put($url, $body, $options);
                break;
            case 'DELETE':
                $raw = $curl->delete($url, [], $options);
                break;
            default:
                throw new \coding_exception('Unsupported HTTP method.');
        }
        $this->assert_success($curl);
        $response = json_decode((string)$raw, true);
        return is_array($response) ? $response : [];
    }

    /**
     * Throws a sanitised exception for transport or HTTP errors.
     *
     * @param \curl $curl Curl instance after the request.
     * @throws api_exception
     */
    protected function assert_success(\curl $curl): void {
        $info = $curl->get_info();
        $code = (int)($info['http_code'] ?? 0);
        $curlerror = (int)$curl->get_errno();
        if ($curlerror || $code >= 400) {
            $this->log_debug('FredKI HTTP failure (' . $code . ', cURL ' . $curlerror . ').');
            $transient = $curlerror !== 0 || $code === 0 || $code === 408 || $code === 425 ||
                $code === 429 || $code >= 500;
            throw new api_exception($transient, $code);
        }
    }
}
