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
 * The tutor chat of a Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use aiprovider_lumination\provider;
use mod_luminationtutor\tutor;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'luminationtutor');
$instance = $DB->get_record('luminationtutor', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/luminationtutor:view', $context);
luminationtutor_view($instance, $course, $cm, $context);

$PAGE->set_url('/mod/luminationtutor/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));

$tutor = new tutor($instance, (int) $cm->id);
$canmanage = has_capability('moodle/course:manageactivities', $context);
$canuse = !isguestuser() && has_capability('mod/luminationtutor:use', $context);

$notices = [];
if (!$tutor->is_enabled()) {
    $notices[] = get_string('tutoroffteacher', 'mod_luminationtutor');
}
if (!$tutor->get_document()) {
    $notices[] = get_string('nodocumentteacher', 'mod_luminationtutor');
}
if (!provider::get_site_instance()) {
    $notices[] = get_string('noprovider', 'mod_luminationtutor');
}

echo $OUTPUT->header();

if ($canmanage) {
    foreach ($notices as $notice) {
        echo $OUTPUT->notification($notice, 'warning', false);
    }
}

if ($canuse && !$notices) {
    $remaining = $tutor->remaining_today((int) $USER->id);
    $messages = array_map([$tutor, 'export_message'], $tutor->get_history((int) $USER->id));
    $PAGE->requires->js_call_amd('mod_luminationtutor/chat', 'init', [(int) $cm->id]);
    echo $OUTPUT->render_from_template('mod_luminationtutor/chat', [
        'cmid' => (int) $cm->id,
        'messages' => $messages,
        'hasmessages' => !empty($messages),
        'maxlength' => tutor::MAX_MESSAGE_LENGTH,
        'remainingtext' => get_string('remaining', 'mod_luminationtutor', $remaining),
        'cansend' => $remaining > 0,
    ]);
} else if (!$canmanage) {
    echo $OUTPUT->notification(get_string('tutornotavailable', 'mod_luminationtutor'), 'info', false);
}

if ($canmanage) {
    echo html_writer::tag('p', get_string('usage', 'mod_luminationtutor', $tutor->get_usage()), ['class' => 'text-muted mt-3']);
}

echo $OUTPUT->footer();
