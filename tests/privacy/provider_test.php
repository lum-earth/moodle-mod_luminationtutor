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
 * Unit tests for the privacy provider.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace mod_luminationtutor\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for {@see \mod_luminationtutor\privacy\provider}.
 *
 * @covers \mod_luminationtutor\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * A tutor activity in a new course.
     *
     * @return array [activity record, its context].
     */
    private function create_tutor(): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $tutor = $this->getDataGenerator()->create_module('luminationtutor', ['course' => $course->id]);
        return [$tutor, \context_module::instance($tutor->cmid)];
    }

    /**
     * Store a conversation with one answered question.
     *
     * @param \stdClass $tutor The activity.
     * @param int $userid The user.
     * @return void
     */
    private function add_conversation(\stdClass $tutor, int $userid): void {
        global $DB;
        $now = time();
        $convid = $DB->insert_record('luminationtutor_conv', [
            'luminationtutorid' => $tutor->id,
            'userid' => $userid,
            'conversationid' => 'conv-' . $userid,
            'documenthash' => '',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('luminationtutor_msg', [
            'convid' => $convid,
            'conversationid' => 'conv-' . $userid,
            'luminationtutorid' => $tutor->id,
            'userid' => $userid,
            'message' => 'Question from ' . $userid,
            'reply' => 'Answer',
            'requestid' => 'req-' . $userid,
            'status' => 'completed',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Test finding, exporting and deleting one user's data.
     */
    public function test_user_data(): void {
        global $DB;
        $this->resetAfterTest();
        [$tutor, $context] = $this->create_tutor();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->add_conversation($tutor, $user->id);
        $this->add_conversation($tutor, $other->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertEquals([$context->id], $contextlist->get_contextids());

        $approved = new approved_contextlist($user, 'mod_luminationtutor', [$context->id]);
        provider::export_user_data($approved);
        $data = writer::with_context($context)->get_data([get_string('pluginname', 'mod_luminationtutor')]);
        $this->assertSame('conv-' . $user->id, $data->conversationid);
        $this->assertCount(1, $data->messages);
        $this->assertSame('Question from ' . $user->id, $data->messages[0]->message);

        provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('luminationtutor_conv', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('luminationtutor_msg', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('luminationtutor_msg', ['userid' => $other->id]));
    }

    /**
     * Test listing and deleting the users of an activity.
     */
    public function test_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        [$tutor, $context] = $this->create_tutor();
        $users = [$this->getDataGenerator()->create_user(), $this->getDataGenerator()->create_user()];
        foreach ($users as $user) {
            $this->add_conversation($tutor, $user->id);
        }

        $userlist = new userlist($context, 'mod_luminationtutor');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$users[0]->id, $users[1]->id], $userlist->get_userids());

        provider::delete_data_for_users(new approved_userlist($context, 'mod_luminationtutor', [$users[0]->id]));
        $this->assertSame(1, $DB->count_records('luminationtutor_conv'));
        $this->assertSame(1, $DB->count_records('luminationtutor_msg'));

        provider::delete_data_for_all_users_in_context($context);
        $this->assertSame(0, $DB->count_records('luminationtutor_conv'));
        $this->assertSame(0, $DB->count_records('luminationtutor_msg'));
    }
}
