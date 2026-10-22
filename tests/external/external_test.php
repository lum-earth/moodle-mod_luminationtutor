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
 * Unit tests for the chat's external functions.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace mod_luminationtutor\external;

/**
 * Tests for the external functions. The test site has no Lumination provider, so nothing reaches the API.
 *
 * @covers \mod_luminationtutor\external\send_message
 * @covers \mod_luminationtutor\external\get_reply
 */
final class external_test extends \advanced_testcase {
    /**
     * A course with a tutor activity that has a document.
     *
     * @return array [course, activity record with cmid].
     */
    private function create_course_with_tutor(): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $tutor = $this->getDataGenerator()->create_module('luminationtutor', ['course' => $course->id]);
        return [$course, $tutor];
    }

    /**
     * Test that an enrolled student gets the clear "not set up" error when the site has no provider.
     */
    public function test_send_message_without_provider(): void {
        $this->resetAfterTest();
        [$course, $tutor] = $this->create_course_with_tutor();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $this->expectExceptionObject(new \moodle_exception('noprovider', 'mod_luminationtutor'));
        send_message::execute((int) $tutor->cmid, 'Hello');
    }

    /**
     * Test that users who are not in the course cannot use the tutor.
     */
    public function test_send_message_requires_course_access(): void {
        $this->resetAfterTest();
        [, $tutor] = $this->create_course_with_tutor();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\require_login_exception::class);
        send_message::execute((int) $tutor->cmid, 'Hello');
    }

    /**
     * Test that a user can only poll their own messages.
     */
    public function test_get_reply_own_messages_only(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $tutor] = $this->create_course_with_tutor();
        $owner = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $now = time();
        $convid = $DB->insert_record('luminationtutor_conv', [
            'luminationtutorid' => $tutor->id,
            'userid' => $owner->id,
            'conversationid' => 'conv-1',
            'documenthash' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $msgid = $DB->insert_record('luminationtutor_msg', [
            'convid' => $convid,
            'conversationid' => 'conv-1',
            'luminationtutorid' => $tutor->id,
            'userid' => $owner->id,
            'message' => 'Hello',
            'reply' => 'Hi **there**',
            'requestid' => 'req-1',
            'status' => 'completed',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $this->setUser($owner);
        $result = get_reply::execute($msgid);
        $result = \core_external\external_api::clean_returnvalue(get_reply::execute_returns(), $result);
        $this->assertSame('completed', $result['status']);
        $this->assertStringContainsString('<strong>there</strong>', $result['replyhtml']);

        $this->setUser($other);
        $this->expectException(\dml_missing_record_exception::class);
        get_reply::execute($msgid);
    }
}
