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
 * List of the Lumination AI Tutor activities in a course.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$event = \mod_luminationtutor\event\course_module_instance_list_viewed::create([
    'context' => context_course::instance($course->id),
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strplural = get_string('modulenameplural', 'mod_luminationtutor');
$PAGE->set_url('/mod/luminationtutor/index.php', ['id' => $course->id]);
$PAGE->set_title($strplural);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($strplural);

echo $OUTPUT->header();
echo $OUTPUT->heading($strplural);

$tutors = get_all_instances_in_course('luminationtutor', $course);
if (!$tutors) {
    notice(get_string('thereareno', 'moodle', $strplural), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('moduleintro')];
foreach ($tutors as $tutor) {
    $attributes = $tutor->visible ? [] : ['class' => 'dimmed'];
    $link = html_writer::link(
        new moodle_url('/mod/luminationtutor/view.php', ['id' => $tutor->coursemodule]),
        format_string($tutor->name),
        $attributes
    );
    $table->data[] = [$link, format_module_intro('luminationtutor', $tutor, $tutor->coursemodule)];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
