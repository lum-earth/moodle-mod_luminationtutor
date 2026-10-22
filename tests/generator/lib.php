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
 * Data generator for the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @category   test
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Data generator for the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @category   test
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_luminationtutor_generator extends testing_module_generator {
    /**
     * Create an activity, with a text document unless documentcontent is null.
     *
     * @param array|stdClass|null $record Activity fields, plus documentcontent and documentname.
     * @param array|null $options Course module options.
     * @return stdClass The activity record with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        global $USER;
        $record = (array) $record + [
            'enabled' => 1,
            'dailylimit' => 20,
            'documentcontent' => 'Pell is the largest moon of Zorbax.',
            'documentname' => 'notes.txt',
        ];
        if ($record['documentcontent'] !== null && !isset($record['document'])) {
            $record['document'] = file_get_unused_draft_itemid();
            get_file_storage()->create_file_from_string([
                'contextid' => context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $record['document'],
                'filepath' => '/',
                'filename' => $record['documentname'],
            ], $record['documentcontent']);
        }
        unset($record['documentcontent'], $record['documentname']);
        return parent::create_instance($record, $options);
    }
}
