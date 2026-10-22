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
 * Unit tests for the activity's library functions.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace mod_luminationtutor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/luminationtutor/lib.php');
require_once(__DIR__ . '/fixtures/fake_api_client.php');

/**
 * Tests for the functions in lib.php.
 *
 * @covers ::luminationtutor_add_instance
 * @covers ::luminationtutor_update_instance
 * @covers ::luminationtutor_delete_instance
 * @covers ::luminationtutor_view
 */
final class lib_test extends \advanced_testcase {
    /**
     * Test that a new activity keeps its settings and document.
     */
    public function test_add_instance(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $record = $this->getDataGenerator()->create_module('luminationtutor', [
            'course' => $course->id,
            'dailylimit' => 5,
            'documentcontent' => 'Quill is made of ice.',
            'documentname' => 'unit1.txt',
        ]);

        $tutor = tutor::from_cmid((int) $record->cmid);
        $this->assertTrue($tutor->is_enabled());
        $this->assertSame(5, $tutor->get_daily_limit());
        $this->assertSame('unit1.txt', $tutor->get_document()->get_filename());
        $this->assertSame('Quill is made of ice.', $tutor->get_document()->get_content());
    }

    /**
     * Test that updating replaces the document and the settings.
     */
    public function test_update_instance(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $record = $this->getDataGenerator()->create_module('luminationtutor', ['course' => $course->id]);

        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'unit2.txt',
        ], 'Tamsin orbits backwards.');
        luminationtutor_update_instance((object) [
            'instance' => $record->id,
            'coursemodule' => $record->cmid,
            'name' => 'Unit 2 tutor',
            'enabled' => 0,
            'dailylimit' => 3,
            'document' => $draftid,
        ]);

        $tutor = tutor::from_cmid((int) $record->cmid);
        $this->assertFalse($tutor->is_enabled());
        $this->assertSame(3, $tutor->get_daily_limit());
        $this->assertSame('unit2.txt', $tutor->get_document()->get_filename());
    }

    /**
     * Test that deleting the activity deletes its conversations and messages.
     */
    public function test_delete_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $record = $this->getDataGenerator()->create_module('luminationtutor', ['course' => $course->id]);
        $tutor = tutor::from_cmid((int) $record->cmid);
        $tutor->send((int) $this->getDataGenerator()->create_user()->id, 'Hello', new fake_api_client());
        $this->assertSame(1, $DB->count_records('luminationtutor_msg'));

        // Moodle 5.2 moved course_delete_module() to cmactions.
        if (method_exists(\core_courseformat\local\cmactions::class, 'delete')) {
            \core_courseformat\formatactions::cm($course)->delete((int) $record->cmid);
        } else {
            course_delete_module($record->cmid);
        }
        $this->assertFalse($DB->record_exists('luminationtutor', ['id' => $record->id]));
        $this->assertSame(0, $DB->count_records('luminationtutor_conv'));
        $this->assertSame(0, $DB->count_records('luminationtutor_msg'));
    }

    /**
     * Test that viewing triggers the event and marks the activity as viewed for completion.
     */
    public function test_view(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $record = $this->getDataGenerator()->create_module(
            'luminationtutor',
            ['course' => $course->id],
            ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED]
        );
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        $cm = get_coursemodule_from_id('luminationtutor', $record->cmid);
        $context = \context_module::instance($cm->id);

        $sink = $this->redirectEvents();
        luminationtutor_view($DB->get_record('luminationtutor', ['id' => $record->id]), $course, $cm, $context);
        $events = $sink->get_events();
        $sink->close();

        $viewed = array_filter($events, fn($event) => $event instanceof \mod_luminationtutor\event\course_module_viewed);
        $this->assertCount(1, $viewed);
        $completion = new \completion_info($course);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $student->id)->completionstate);
    }
}
