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
 * Library functions of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_luminationtutor\tutor;

/**
 * Features the activity supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True or false for a feature, the purpose for FEATURE_MOD_PURPOSE, null if unknown.
 */
function luminationtutor_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_INTRO,
        FEATURE_SHOW_DESCRIPTION,
        FEATURE_COMPLETION_TRACKS_VIEWS,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_GROUPS,
        FEATURE_GROUPINGS,
        FEATURE_GRADE_HAS_GRADE,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_INTERACTIVECONTENT,
        default => null,
    };
}

/**
 * Create an activity.
 *
 * @param stdClass $data Form data, with the course module id in coursemodule.
 * @param mod_luminationtutor_mod_form|null $mform The form.
 * @return int The new luminationtutor id.
 */
function luminationtutor_add_instance(stdClass $data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('luminationtutor', $data);
    luminationtutor_save_document($data);
    return $data->id;
}

/**
 * Update an activity.
 *
 * @param stdClass $data Form data, with the luminationtutor id in instance.
 * @param mod_luminationtutor_mod_form|null $mform The form.
 * @return bool True.
 */
function luminationtutor_update_instance(stdClass $data, $mform = null) {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    $DB->update_record('luminationtutor', $data);
    luminationtutor_save_document($data);
    return true;
}

/**
 * Delete an activity with its conversations. Moodle deletes the files with the context.
 *
 * @param int $id The luminationtutor id.
 * @return bool True if it existed.
 */
function luminationtutor_delete_instance($id) {
    global $DB;
    if (!$DB->record_exists('luminationtutor', ['id' => $id])) {
        return false;
    }
    tutor::delete_instance_data((int) $id);
    $DB->delete_records('luminationtutor', ['id' => $id]);
    return true;
}

/**
 * Move the uploaded document from its draft area into the activity.
 *
 * @param stdClass $data Form data with coursemodule and document (draft item id).
 * @return void
 */
function luminationtutor_save_document(stdClass $data): void {
    if (!isset($data->document)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files($data->document, $context->id, 'mod_luminationtutor', tutor::FILEAREA, 0, tutor::file_options());
}

/**
 * Course page information: the description when it is shown on the course page.
 *
 * @param stdClass $coursemodule The course module.
 * @return cached_cm_info|null The information, or null if the activity is missing.
 */
function luminationtutor_get_coursemodule_info($coursemodule) {
    global $DB;
    $tutor = $DB->get_record('luminationtutor', ['id' => $coursemodule->instance], 'id, name, intro, introformat');
    if (!$tutor) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $tutor->name;
    if ($coursemodule->showdescription) {
        // Filters run at display time.
        $info->content = format_module_intro('luminationtutor', $tutor, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Record that the activity was viewed: the event and view completion.
 *
 * @param stdClass $tutor The luminationtutor record.
 * @param stdClass $course The course.
 * @param cm_info|stdClass $cm The course module.
 * @param context_module $context The activity context.
 * @return void
 */
function luminationtutor_view(stdClass $tutor, stdClass $course, $cm, context_module $context): void {
    $event = \mod_luminationtutor\event\course_module_viewed::create([
        'objectid' => $tutor->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('luminationtutor', $tutor);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}
