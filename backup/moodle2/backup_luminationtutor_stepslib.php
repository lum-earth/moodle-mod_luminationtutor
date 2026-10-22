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
 * Backup structure of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure of the Lumination AI Tutor activity.
 *
 * Settings and the document always; conversations and messages only with user data.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_luminationtutor_activity_structure_step extends backup_activity_structure_step {
    /**
     * The structure.
     *
     * @return backup_nested_element The activity element.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $tutor = new backup_nested_element('luminationtutor', ['id'], [
            'name', 'intro', 'introformat', 'enabled', 'dailylimit', 'timecreated', 'timemodified',
        ]);
        $conversations = new backup_nested_element('conversations');
        $conversation = new backup_nested_element('conversation', ['id'], [
            'userid', 'conversationid', 'documenthash', 'timecreated', 'timemodified',
        ]);
        $messages = new backup_nested_element('messages');
        $message = new backup_nested_element('message', ['id'], [
            'conversationid', 'userid', 'message', 'reply', 'requestid', 'status', 'timecreated', 'timemodified',
        ]);

        $tutor->add_child($conversations);
        $conversations->add_child($conversation);
        $conversation->add_child($messages);
        $messages->add_child($message);

        $tutor->set_source_table('luminationtutor', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $conversation->set_source_table('luminationtutor_conv', ['luminationtutorid' => backup::VAR_PARENTID], 'id ASC');
            $message->set_source_table('luminationtutor_msg', ['convid' => backup::VAR_PARENTID], 'id ASC');
        }

        $conversation->annotate_ids('user', 'userid');
        $message->annotate_ids('user', 'userid');

        $tutor->annotate_files('mod_luminationtutor', 'intro', null);
        $tutor->annotate_files('mod_luminationtutor', 'document', null);

        return $this->prepare_activity_structure($tutor);
    }
}
