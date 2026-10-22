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
 * The tutor of one activity: settings, conversations and messages.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_luminationtutor;

use aiprovider_lumination\api_client;
use aiprovider_lumination\api_exception;

/**
 * The tutor of one activity: settings, conversations and messages.
 *
 * The AI Tutor API keeps the conversation: the first message carries the source document
 * and gets a conversation_id back, later messages send only that id. Replies are async, so
 * send() submits a message and check_reply() polls for the answer. One conversation per
 * user and activity; a new one starts when the teacher changes the document.
 *
 * Only the message text, the conversation id and the teacher's document are sent to the
 * API. Never the student's name or email.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tutor {
    /** @var string File area holding the source document, item 0, in the module context. */
    public const FILEAREA = 'document';

    /** @var int Default number of messages a student can send per day. */
    public const DEFAULT_DAILY_LIMIT = 20;

    /** @var int Longest message accepted, in characters. */
    public const MAX_MESSAGE_LENGTH = 2000;

    /** @var int Seconds after which an unanswered message is given up. */
    public const REPLY_TIMEOUT = 600;

    /** @var int Messages shown in the chat when the page loads. */
    public const HISTORY_LENGTH = 20;

    /** @var string Table of conversations. */
    private const CONV_TABLE = 'luminationtutor_conv';

    /** @var string Table of messages. */
    private const MSG_TABLE = 'luminationtutor_msg';

    /** @var \stdClass The luminationtutor record. */
    private \stdClass $instance;

    /** @var int The course module id. */
    private int $cmid;

    /**
     * Constructor.
     *
     * @param \stdClass $instance The luminationtutor record.
     * @param int $cmid Its course module id.
     */
    public function __construct(\stdClass $instance, int $cmid) {
        $this->instance = $instance;
        $this->cmid = $cmid;
    }

    /**
     * The tutor of a course module.
     *
     * @param int $cmid The course module id.
     * @return self The tutor.
     * @throws \moodle_exception If there is no Lumination AI Tutor with this id.
     */
    public static function from_cmid(int $cmid): self {
        global $DB;
        $cm = get_coursemodule_from_id('luminationtutor', $cmid, 0, false, MUST_EXIST);
        return new self($DB->get_record('luminationtutor', ['id' => $cm->instance], '*', MUST_EXIST), (int) $cm->id);
    }

    /**
     * The tutor of an activity.
     *
     * @param int $instanceid The luminationtutor id.
     * @return self The tutor.
     * @throws \moodle_exception If there is no Lumination AI Tutor with this id.
     */
    public static function from_instance_id(int $instanceid): self {
        global $DB;
        $instance = $DB->get_record('luminationtutor', ['id' => $instanceid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('luminationtutor', $instanceid, $instance->course, false, MUST_EXIST);
        return new self($instance, (int) $cm->id);
    }

    /**
     * Options of the source document file manager.
     *
     * @return array File manager options.
     */
    public static function file_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.pdf', '.docx', '.txt'],
        ];
    }

    /**
     * The activity id.
     *
     * @return int The luminationtutor id.
     */
    public function get_instance_id(): int {
        return (int) $this->instance->id;
    }

    /**
     * The course module id.
     *
     * @return int The id.
     */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /**
     * The activity context.
     *
     * @return \context_module The context.
     */
    public function get_context(): \context_module {
        return \context_module::instance($this->cmid);
    }

    /**
     * Whether the teacher has switched the tutor on.
     *
     * @return bool True if students can ask questions.
     */
    public function is_enabled(): bool {
        return !empty($this->instance->enabled);
    }

    /**
     * Messages each student can send per day.
     *
     * @return int The limit, at least 1.
     */
    public function get_daily_limit(): int {
        return max(1, (int) ($this->instance->dailylimit ?? self::DEFAULT_DAILY_LIMIT));
    }

    /**
     * The source document the tutor answers from.
     *
     * @return \stored_file|null The file, or null if the teacher has not added one.
     */
    public function get_document(): ?\stored_file {
        $files = get_file_storage()->get_area_files(
            $this->get_context()->id,
            'mod_luminationtutor',
            self::FILEAREA,
            0,
            'id',
            false
        );
        return $files ? reset($files) : null;
    }

    /**
     * Messages a user has sent today that count toward the daily limit (failed ones do not).
     *
     * @param int $userid The user.
     * @return int Number of messages since the user's midnight.
     */
    public function messages_today(int $userid): int {
        global $DB;
        return $DB->count_records_select(
            self::MSG_TABLE,
            'luminationtutorid = :luminationtutorid AND userid = :userid AND timecreated >= :midnight AND status <> :failed',
            [
                'luminationtutorid' => $this->get_instance_id(),
                'userid' => $userid,
                'midnight' => usergetmidnight(time()),
                'failed' => 'failed',
            ]
        );
    }

    /**
     * Messages a user can still send today.
     *
     * @param int $userid The user.
     * @return int Messages left, 0 or more.
     */
    public function remaining_today(int $userid): int {
        return max(0, $this->get_daily_limit() - $this->messages_today($userid));
    }

    /**
     * The user's conversation in this activity.
     *
     * @param int $userid The user.
     * @return \stdClass|null The conversation record, or null if the user has not asked anything yet.
     */
    public function get_conversation(int $userid): ?\stdClass {
        global $DB;
        $conv = $DB->get_record(self::CONV_TABLE, ['luminationtutorid' => $this->get_instance_id(), 'userid' => $userid]);
        return $conv ?: null;
    }

    /**
     * The latest messages of the user's conversation about the current document, oldest first.
     *
     * @param int $userid The user.
     * @return \stdClass[] Message records.
     */
    public function get_history(int $userid): array {
        global $DB;
        $conv = $this->get_conversation($userid);
        $document = $this->get_document();
        if (!$conv || !$conv->conversationid || !$document || $conv->documenthash !== $document->get_contenthash()) {
            return [];
        }
        $messages = $DB->get_records(
            self::MSG_TABLE,
            ['convid' => $conv->id, 'conversationid' => $conv->conversationid],
            'timecreated DESC, id DESC',
            '*',
            0,
            self::HISTORY_LENGTH
        );
        return array_reverse(array_values($messages));
    }

    /**
     * Send a message to the tutor. The reply comes later through check_reply().
     *
     * @param int $userid The user asking.
     * @param string $message The question.
     * @param api_client|null $client The API client, or null if the site has no Lumination provider.
     * @return \stdClass The new message record, status pending.
     * @throws \moodle_exception If the message cannot be sent (tutor off, limit reached, and so on).
     * @throws api_exception If the API call fails.
     */
    public function send(int $userid, string $message, ?api_client $client): \stdClass {
        global $DB;

        $message = trim($message);
        if ($message === '') {
            throw new \moodle_exception('emptymessage', 'mod_luminationtutor');
        }
        if (\core_text::strlen($message) > self::MAX_MESSAGE_LENGTH) {
            throw new \moodle_exception('messagetoolong', 'mod_luminationtutor', '', self::MAX_MESSAGE_LENGTH);
        }
        if (!$this->is_enabled()) {
            throw new \moodle_exception('tutoroff', 'mod_luminationtutor');
        }
        $document = $this->get_document();
        if (!$document) {
            throw new \moodle_exception('nodocument', 'mod_luminationtutor');
        }
        if ($this->remaining_today($userid) === 0) {
            throw new \moodle_exception('dailylimitreached', 'mod_luminationtutor', '', $this->get_daily_limit());
        }
        if ($this->has_pending_message($userid)) {
            throw new \moodle_exception('waitforreply', 'mod_luminationtutor');
        }
        if (!$client) {
            throw new \moodle_exception('noprovider', 'mod_luminationtutor');
        }

        $conv = $this->start_conversation($userid, $document->get_contenthash());
        $request = ['message' => $message];
        if ($conv->conversationid) {
            $request['conversation_id'] = $conv->conversationid;
        } else {
            // The document grounds the whole conversation, so it goes with the first message only.
            $request['file_b64'] = base64_encode($document->get_content());
            $request['file_name'] = $document->get_filename();
        }

        $response = $client->post('/tutor', $request);
        $requestid = (string) ($response['request_id'] ?? '');
        if ($requestid === '') {
            throw new \moodle_exception('noreply', 'mod_luminationtutor');
        }
        if (!$conv->conversationid && !empty($response['conversation_id'])) {
            $conv->conversationid = (string) $response['conversation_id'];
            $conv->timemodified = time();
            $DB->update_record(self::CONV_TABLE, $conv);
        }

        $now = time();
        $msg = (object) [
            'convid' => $conv->id,
            'conversationid' => (string) $conv->conversationid,
            'luminationtutorid' => $this->get_instance_id(),
            'userid' => $userid,
            'message' => $message,
            'reply' => null,
            'requestid' => $requestid,
            'status' => 'pending',
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $msg->id = $DB->insert_record(self::MSG_TABLE, $msg);
        return $msg;
    }

    /**
     * Ask the API whether a pending message has been answered, and store the reply.
     *
     * @param \stdClass $msg The message record (updated in place).
     * @param api_client|null $client The API client, or null if the site has no Lumination provider.
     * @return \stdClass The message, status pending, completed or failed.
     */
    public function check_reply(\stdClass $msg, ?api_client $client): \stdClass {
        if ($msg->status !== 'pending') {
            return $msg;
        }
        if (!$client || time() - (int) $msg->timecreated > self::REPLY_TIMEOUT) {
            return $this->finish($msg, 'failed');
        }

        try {
            $job = $client->get('/requests/' . rawurlencode($msg->requestid));
        } catch (api_exception $e) {
            // A rejected key or no credits will not get better; anything else is retried at the next poll.
            return $e->is_fatal() ? $this->finish($msg, 'failed') : $msg;
        }

        $status = $job['status'] ?? '';
        if ($status === 'completed') {
            $reply = $job['result']['reply'] ?? '';
            if (!is_string($reply) || trim($reply) === '') {
                return $this->finish($msg, 'failed');
            }
            return $this->finish($msg, 'completed', trim($reply));
        }
        if ($status === 'failed') {
            return $this->finish($msg, 'failed');
        }
        return $msg;
    }

    /**
     * A reply as HTML for the chat. Replies are Markdown.
     *
     * @param \stdClass $msg The message record.
     * @return string Safe HTML, or '' if there is no reply yet.
     */
    public function format_reply(\stdClass $msg): string {
        if ($msg->status !== 'completed' || $msg->reply === null) {
            return '';
        }
        return format_text($msg->reply, FORMAT_MARKDOWN, ['context' => $this->get_context()]);
    }

    /**
     * A message for the chat template (templates/message.mustache).
     *
     * @param \stdClass $msg The message record.
     * @return array Template context.
     */
    public function export_message(\stdClass $msg): array {
        return [
            'id' => (int) $msg->id,
            'message' => $msg->message,
            'replyhtml' => $this->format_reply($msg),
            'pending' => $msg->status === 'pending',
            'failed' => $msg->status === 'failed',
        ];
    }

    /**
     * Usage of this activity, for teachers.
     *
     * @return \stdClass Messages today, students today and messages in total.
     */
    public function get_usage(): \stdClass {
        global $DB;
        $params = [
            'luminationtutorid' => $this->get_instance_id(),
            'midnight' => usergetmidnight(time()),
            'failed' => 'failed',
        ];
        $today = $DB->get_record_sql(
            'SELECT COUNT(1) AS messages, COUNT(DISTINCT userid) AS students
               FROM {' . self::MSG_TABLE . '}
              WHERE luminationtutorid = :luminationtutorid AND timecreated >= :midnight AND status <> :failed',
            $params
        );
        $total = $DB->count_records_select(
            self::MSG_TABLE,
            'luminationtutorid = :luminationtutorid AND status <> :failed',
            $params
        );
        return (object) [
            'messages' => (int) $today->messages,
            'students' => (int) $today->students,
            'total' => $total,
        ];
    }

    /**
     * Delete the conversations and messages of an activity.
     *
     * @param int $luminationtutorid The luminationtutor id.
     * @return void
     */
    public static function delete_instance_data(int $luminationtutorid): void {
        global $DB;
        $DB->delete_records(self::MSG_TABLE, ['luminationtutorid' => $luminationtutorid]);
        $DB->delete_records(self::CONV_TABLE, ['luminationtutorid' => $luminationtutorid]);
    }

    /**
     * Whether the user is still waiting for a reply.
     *
     * @param int $userid The user.
     * @return bool True if a message sent in the last REPLY_TIMEOUT seconds has no reply yet.
     */
    private function has_pending_message(int $userid): bool {
        global $DB;
        return $DB->record_exists_select(
            self::MSG_TABLE,
            'luminationtutorid = :luminationtutorid AND userid = :userid AND status = :pending AND timecreated > :since',
            [
                'luminationtutorid' => $this->get_instance_id(),
                'userid' => $userid,
                'pending' => 'pending',
                'since' => time() - self::REPLY_TIMEOUT,
            ]
        );
    }

    /**
     * The user's conversation about the current document, created or restarted if needed.
     *
     * @param int $userid The user.
     * @param string $documenthash Content hash of the current document.
     * @return \stdClass The conversation record; conversationid is null until the API has answered once.
     */
    private function start_conversation(int $userid, string $documenthash): \stdClass {
        global $DB;
        $now = time();
        $conv = $this->get_conversation($userid);
        if (!$conv) {
            $conv = (object) [
                'luminationtutorid' => $this->get_instance_id(),
                'userid' => $userid,
                'conversationid' => null,
                'documenthash' => $documenthash,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $conv->id = $DB->insert_record(self::CONV_TABLE, $conv);
            return $conv;
        }
        if ($conv->documenthash !== $documenthash) {
            // The teacher changed the document: start over. Old messages stay for the daily limit and usage.
            $conv->conversationid = null;
            $conv->documenthash = $documenthash;
            $conv->timemodified = $now;
            $DB->update_record(self::CONV_TABLE, $conv);
        }
        return $conv;
    }

    /**
     * Store the outcome of a message.
     *
     * @param \stdClass $msg The message record (updated in place).
     * @param string $status completed or failed.
     * @param string|null $reply The reply, for completed messages.
     * @return \stdClass The message.
     */
    private function finish(\stdClass $msg, string $status, ?string $reply = null): \stdClass {
        global $DB;
        $msg->status = $status;
        $msg->reply = $reply;
        $msg->timemodified = time();
        $DB->update_record(self::MSG_TABLE, $msg);
        return $msg;
    }
}
