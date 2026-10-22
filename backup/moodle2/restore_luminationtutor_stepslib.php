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
 * Restore structure of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_luminationtutor_activity_structure_step extends restore_activity_structure_step {
    /**
     * The paths to restore; conversations only with user data.
     *
     * @return restore_path_element[] The paths.
     */
    protected function define_structure() {
        $paths = [new restore_path_element('luminationtutor', '/activity/luminationtutor')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'luminationtutor_conversation',
                '/activity/luminationtutor/conversations/conversation'
            );
            $paths[] = new restore_path_element(
                'luminationtutor_message',
                '/activity/luminationtutor/conversations/conversation/messages/message'
            );
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the activity.
     *
     * @param array $data The activity data.
     * @return void
     */
    protected function process_luminationtutor($data) {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('luminationtutor', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore a conversation.
     *
     * @param array $data The conversation data.
     * @return void
     */
    protected function process_luminationtutor_conversation($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        $data->luminationtutorid = $this->get_new_parentid('luminationtutor');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }
        $newitemid = $DB->insert_record('luminationtutor_conv', $data);
        $this->set_mapping('luminationtutor_conversation', $oldid, $newitemid);
    }

    /**
     * Restore a message.
     *
     * @param array $data The message data.
     * @return void
     */
    protected function process_luminationtutor_message($data) {
        global $DB;
        $data = (object) $data;
        $data->convid = $this->get_new_parentid('luminationtutor_conversation');
        $data->luminationtutorid = $this->get_new_parentid('luminationtutor');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->convid || !$data->userid) {
            return;
        }
        $DB->insert_record('luminationtutor_msg', $data);
    }

    /**
     * Restore the description files and the document.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_luminationtutor', 'intro', null);
        $this->add_related_files('mod_luminationtutor', 'document', null);
    }
}
