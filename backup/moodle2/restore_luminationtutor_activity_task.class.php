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
 * Restore task of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/luminationtutor/backup/moodle2/restore_luminationtutor_stepslib.php');

/**
 * Restore task of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_luminationtutor_activity_task extends restore_activity_task {
    /**
     * No settings of its own.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * One step reads luminationtutor.xml.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_luminationtutor_activity_structure_step('luminationtutor_structure', 'luminationtutor.xml'));
    }

    /**
     * Fields whose links are decoded.
     *
     * @return restore_decode_content[] The contents.
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('luminationtutor', ['intro'], 'luminationtutor')];
    }

    /**
     * How encoded links are turned back into URLs.
     *
     * @return restore_decode_rule[] The rules.
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('LUMINATIONTUTORVIEWBYID', '/mod/luminationtutor/view.php?id=$1', 'course_module'),
            new restore_decode_rule('LUMINATIONTUTORINDEX', '/mod/luminationtutor/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Legacy log rules for the activity.
     *
     * @return restore_log_rule[] The rules.
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('luminationtutor', 'add', 'view.php?id={course_module}', '{luminationtutor}'),
            new restore_log_rule('luminationtutor', 'update', 'view.php?id={course_module}', '{luminationtutor}'),
            new restore_log_rule('luminationtutor', 'view', 'view.php?id={course_module}', '{luminationtutor}'),
        ];
    }

    /**
     * Legacy log rules for the course.
     *
     * @return restore_log_rule[] The rules.
     */
    public static function define_restore_log_rules_for_course() {
        return [new restore_log_rule('luminationtutor', 'view all', 'index.php?id={course}', null)];
    }
}
