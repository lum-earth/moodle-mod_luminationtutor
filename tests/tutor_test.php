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
 * Unit tests for the tutor of an activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace mod_luminationtutor;

use aiprovider_lumination\api_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/fake_api_client.php');

/**
 * Tests for {@see \mod_luminationtutor\tutor}. No real API calls.
 *
 * @covers \mod_luminationtutor\tutor
 */
final class tutor_test extends \advanced_testcase {
    /**
     * A tutor activity in a new course, with a text document.
     *
     * @param array $settings Activity settings.
     * @param string|null $document Content of the document, or null for none.
     * @return tutor The tutor.
     */
    private function create_tutor(array $settings = [], ?string $document = 'Pell is the largest moon of Zorbax.'): tutor {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(
            'luminationtutor',
            ['course' => $course->id, 'documentcontent' => $document] + $settings
        );
        return tutor::from_cmid((int) $instance->cmid);
    }

    /**
     * Replace the tutor's document, as the settings form does.
     *
     * @param tutor $tutor The tutor.
     * @param string $filename File name.
     * @param string $content File content.
     * @return void
     */
    private function set_document(tutor $tutor, string $filename, string $content): void {
        $fs = get_file_storage();
        $contextid = $tutor->get_context()->id;
        $fs->delete_area_files($contextid, 'mod_luminationtutor', tutor::FILEAREA);
        $fs->create_file_from_string([
            'contextid' => $contextid,
            'component' => 'mod_luminationtutor',
            'filearea' => tutor::FILEAREA,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
    }

    /**
     * Test that the first message carries the document and later ones only the conversation id.
     */
    public function test_send_and_reply(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Alicia', 'email' => 'alicia@example.com']);
        $tutor = $this->create_tutor();
        $client = new fake_api_client();

        $msg = $tutor->send($user->id, '  Which moon is the largest?  ', $client);
        $this->assertSame('pending', $msg->status);
        $this->assertSame('Which moon is the largest?', $msg->message);
        $this->assertSame('req-1', $msg->requestid);
        $first = $client->posts[0];
        $this->assertSame('/tutor', $first['path']);
        $this->assertSame(['message', 'file_b64', 'file_name'], array_keys($first['data']));
        $this->assertSame('Pell is the largest moon of Zorbax.', base64_decode($first['data']['file_b64']));
        $this->assertSame('notes.txt', $first['data']['file_name']);
        $this->assertSame('conv-1', $tutor->get_conversation($user->id)->conversationid);

        // Still being written.
        $msg = $tutor->check_reply($msg, $client);
        $this->assertSame('pending', $msg->status);
        $this->assertSame(['/requests/req-1'], $client->gets);

        $client->complete('req-1', 'It is **Pell**.');
        $msg = $tutor->check_reply($msg, $client);
        $this->assertSame('completed', $msg->status);
        $this->assertSame('It is **Pell**.', $DB->get_field('luminationtutor_msg', 'reply', ['id' => $msg->id]));
        $this->assertStringContainsString('<strong>Pell</strong>', $tutor->format_reply($msg));

        $tutor->send($user->id, 'Is it made of ice?', $client);
        $this->assertSame(['message' => 'Is it made of ice?', 'conversation_id' => 'conv-1'], $client->posts[1]['data']);
        $this->assertCount(2, $tutor->get_history($user->id));

        // Nothing that identifies the student goes to the API.
        $sent = json_encode($client->posts);
        $this->assertStringNotContainsString('Alicia', $sent);
        $this->assertStringNotContainsString('alicia@example.com', $sent);
    }

    /**
     * Test that a new document starts a new conversation with that document.
     */
    public function test_new_document_starts_new_conversation(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $tutor = $this->create_tutor();
        $client = new fake_api_client();

        $msg = $tutor->send($user->id, 'First question', $client);
        $client->complete('req-1', 'First answer');
        $tutor->check_reply($msg, $client);
        $this->assertCount(1, $tutor->get_history($user->id));

        $this->set_document($tutor, 'unit2.txt', 'Quill is made of ice.');
        $this->assertSame([], $tutor->get_history($user->id));

        $tutor->send($user->id, 'Second question', $client);
        $data = $client->posts[1]['data'];
        $this->assertArrayNotHasKey('conversation_id', $data);
        $this->assertSame('Quill is made of ice.', base64_decode($data['file_b64']));
        $this->assertSame('conv-2', $tutor->get_conversation($user->id)->conversationid);
        $this->assertCount(1, $tutor->get_history($user->id));
        // The earlier message still counts toward today's limit.
        $this->assertSame(2, $tutor->messages_today($user->id));
    }

    /**
     * Test the daily limit, which failed messages do not use up.
     */
    public function test_daily_limit(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $tutor = $this->create_tutor(['dailylimit' => 2]);
        $client = new fake_api_client();

        $msg = $tutor->send($user->id, 'One', $client);
        $client->jobs['req-1'] = ['request_id' => 'req-1', 'status' => 'failed', 'error' => 'boom'];
        $this->assertSame('failed', $tutor->check_reply($msg, $client)->status);
        $this->assertSame(2, $tutor->remaining_today($user->id));

        foreach (['Two', 'Three'] as $text) {
            $msg = $tutor->send($user->id, $text, $client);
            $client->complete($msg->requestid, 'Answer');
            $tutor->check_reply($msg, $client);
        }
        $this->assertSame(0, $tutor->remaining_today($user->id));

        $this->expectExceptionObject(new \moodle_exception('dailylimitreached', 'mod_luminationtutor', '', 2));
        $tutor->send($user->id, 'Four', $client);
    }

    /**
     * Test that a student waits for a reply before asking again.
     */
    public function test_wait_for_reply(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $tutor = $this->create_tutor();
        $client = new fake_api_client();

        $tutor->send($user->id, 'One', $client);
        $this->expectExceptionObject(new \moodle_exception('waitforreply', 'mod_luminationtutor'));
        $tutor->send($user->id, 'Two', $client);
    }

    /**
     * Test the checks made before anything is sent.
     *
     * @dataProvider send_errors_provider
     * @param array $config Activity settings.
     * @param string|null $document Document content, or null for none.
     * @param string $message The message.
     * @param bool $withclient Whether the site has a Lumination provider.
     * @param string $error Expected error string id.
     */
    public function test_send_errors(array $config, ?string $document, string $message, bool $withclient, string $error): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $tutor = $this->create_tutor($config, $document);
        $client = new fake_api_client();

        try {
            $tutor->send($user->id, $message, $withclient ? $client : null);
            $this->fail('No exception thrown');
        } catch (\moodle_exception $e) {
            $this->assertSame($error, $e->errorcode);
        }
        $this->assertSame([], $client->posts);
    }

    /**
     * Cases for test_send_errors.
     *
     * @return array Test cases.
     */
    public static function send_errors_provider(): array {
        return [
            'empty' => [[], 'Doc', "  \n ", true, 'emptymessage'],
            'too long' => [[], 'Doc', str_repeat('a', tutor::MAX_MESSAGE_LENGTH + 1), true, 'messagetoolong'],
            'switched off' => [['enabled' => 0], 'Doc', 'Hello', true, 'tutoroff'],
            'no document' => [[], null, 'Hello', true, 'nodocument'],
            'no provider' => [[], 'Doc', 'Hello', false, 'noprovider'],
        ];
    }

    /**
     * Test how API failures while polling are handled.
     */
    public function test_check_reply_failures(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $tutor = $this->create_tutor();
        $client = new fake_api_client();
        $msg = $tutor->send($user->id, 'One', $client);

        // A passing problem: try again at the next poll.
        $client->geterror = new api_exception(502, 'Bad gateway', 'ai-tutor.ai', 'moodle-x');
        $this->assertSame('pending', $tutor->check_reply($msg, $client)->status);

        // An empty reply is a failure.
        $client->geterror = null;
        $client->complete('req-1', '  ');
        $this->assertSame('failed', $tutor->check_reply($msg, $client)->status);

        // No credits left: give up.
        $msg = $tutor->send($user->id, 'Two', $client);
        $client->geterror = new api_exception(402, 'No credits', 'ai-tutor.ai', 'moodle-y');
        $this->assertSame('failed', $tutor->check_reply($msg, $client)->status);

        // Too old: give up without asking the API.
        $msg = $tutor->send($user->id, 'Three', $client);
        $msg->timecreated = time() - tutor::REPLY_TIMEOUT - 1;
        $calls = count($client->gets);
        $this->assertSame('failed', $tutor->check_reply($msg, $client)->status);
        $this->assertCount($calls, $client->gets);
    }

    /**
     * Test the defaults and the usage figures for teachers.
     */
    public function test_settings_and_usage(): void {
        $this->resetAfterTest();
        $tutor = $this->create_tutor();
        $this->assertTrue($tutor->is_enabled());
        $this->assertSame(tutor::DEFAULT_DAILY_LIMIT, $tutor->get_daily_limit());

        $client = new fake_api_client();
        foreach ([1, 2] as $n) {
            $user = $this->getDataGenerator()->create_user();
            $msg = $tutor->send($user->id, 'Question ' . $n, $client);
            $client->complete($msg->requestid, 'Answer');
            $tutor->check_reply($msg, $client);
        }
        $usage = $tutor->get_usage();
        $this->assertSame(2, $usage->messages);
        $this->assertSame(2, $usage->students);
        $this->assertSame(2, $usage->total);

        tutor::delete_instance_data($tutor->get_instance_id());
        $this->assertSame(0, $tutor->get_usage()->total);
    }
}
