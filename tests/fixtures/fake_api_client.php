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
 * An AI Tutor API client that records calls and answers like the real /tutor endpoint.
 *
 * @package    mod_luminationtutor
 * @category   test
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_luminationtutor;

use aiprovider_lumination\api_client;

/**
 * An AI Tutor API client that records calls and answers like the real /tutor endpoint.
 *
 * POST /tutor returns {request_id, conversation_id} (shape checked against the API on
 * 22 Oct 2026). GET /requests/{id} returns what the test put in $jobs, or "processing".
 *
 * @package    mod_luminationtutor
 * @category   test
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_api_client extends api_client {
    /** @var array[] POST calls, each ['path' => string, 'data' => array]. */
    public array $posts = [];

    /** @var string[] GET paths called. */
    public array $gets = [];

    /** @var array[] Job envelopes to return, by request id. */
    public array $jobs = [];

    /** @var \Throwable|null Thrown by get() when set. */
    public ?\Throwable $geterror = null;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('sk-tutor-test');
    }

    /**
     * Record the call and return a new request id in the conversation.
     *
     * @param string $path API path.
     * @param array $data Request body.
     * @return array The submit response.
     */
    public function post(string $path, array $data = []): array {
        $this->posts[] = ['path' => $path, 'data' => $data];
        return [
            'request_id' => 'req-' . count($this->posts),
            'conversation_id' => $data['conversation_id'] ?? 'conv-' . count($this->posts),
        ];
    }

    /**
     * Record the call and return the job set by the test.
     *
     * @param string $path API path.
     * @param array $params Query parameters.
     * @return array The job envelope.
     */
    public function get(string $path, array $params = []): array {
        $this->gets[] = $path;
        if ($this->geterror) {
            throw $this->geterror;
        }
        $requestid = substr($path, strlen('/requests/'));
        return $this->jobs[$requestid] ?? ['request_id' => $requestid, 'status' => 'processing'];
    }

    /**
     * Make a request completed with a reply, as the API does.
     *
     * @param string $requestid The request id.
     * @param string $reply The reply.
     * @return void
     */
    public function complete(string $requestid, string $reply): void {
        $this->jobs[$requestid] = [
            'request_id' => $requestid,
            'tool' => 'tutor',
            'status' => 'completed',
            'result' => ['reply' => $reply, 'title' => 'Title'],
            'error' => null,
        ];
    }
}
