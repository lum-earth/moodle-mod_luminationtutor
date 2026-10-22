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
 * Unit tests for backup and restore of the activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace mod_luminationtutor\backup;

use mod_luminationtutor\tutor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/luminationtutor/tests/fixtures/fake_api_client.php');

/**
 * Tests for backup and restore: settings and document always, conversations only with user data.
 *
 * @covers \backup_luminationtutor_activity_structure_step
 * @covers \restore_luminationtutor_activity_structure_step
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * A course with a tutor activity and one answered question from an enrolled student.
     *
     * @return array [course, activity record with cmid].
     */
    private function create_course_with_conversation(): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $record = $this->getDataGenerator()->create_module('luminationtutor', [
            'course' => $course->id,
            'dailylimit' => 7,
            'documentcontent' => 'The capital of Zorbax is Vrenn.',
        ]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $tutor = tutor::from_cmid((int) $record->cmid);
        $client = new \mod_luminationtutor\fake_api_client();
        $msg = $tutor->send((int) $student->id, 'What is the capital?', $client);
        $client->complete($msg->requestid, 'Vrenn.');
        $tutor->check_reply($msg, $client);
        return [$course, $record];
    }

    /**
     * Test a course backup with users restored into a new course.
     */
    public function test_course_backup_with_user_data(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        [$course] = $this->create_course_with_conversation();
        $CFG->backup_file_logger_level = \backup::LOG_NONE;

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $file = $bc->get_results()['backup_destination'];
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $CFG->tempdir . '/backup/lumtutor');
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course('Restored', 'RESTORED', $course->category);
        $rc = new \restore_controller(
            'lumtutor',
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $restored = $DB->get_record('luminationtutor', ['course' => $newcourseid], '*', MUST_EXIST);
        $tutor = tutor::from_instance_id((int) $restored->id);
        $this->assertSame(7, $tutor->get_daily_limit());
        $this->assertSame('The capital of Zorbax is Vrenn.', $tutor->get_document()->get_content());
        $this->assertSame(1, $DB->count_records('luminationtutor_conv', ['luminationtutorid' => $restored->id]));
        $msg = $DB->get_record('luminationtutor_msg', ['luminationtutorid' => $restored->id], '*', MUST_EXIST);
        $this->assertSame('Vrenn.', $msg->reply);
    }

    /**
     * Test that duplicating an activity copies the document but no conversations.
     */
    public function test_duplicate_without_user_data(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $record] = $this->create_course_with_conversation();

        // Moodle 5.2 moved duplicate_module() to cmactions.
        if (method_exists(\core_courseformat\local\cmactions::class, 'duplicate')) {
            $newcm = \core_courseformat\formatactions::cm($course)->duplicate((int) $record->cmid);
        } else {
            $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($record->cmid));
        }

        $tutor = tutor::from_cmid((int) $newcm->id);
        $this->assertSame(7, $tutor->get_daily_limit());
        $this->assertSame('The capital of Zorbax is Vrenn.', $tutor->get_document()->get_content());
        $this->assertSame(0, $DB->count_records('luminationtutor_conv', ['luminationtutorid' => $tutor->get_instance_id()]));
    }
}
