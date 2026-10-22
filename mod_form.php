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
 * Settings form of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

use mod_luminationtutor\tutor;

/**
 * Settings form of the Lumination AI Tutor activity: name, description, document, on/off and daily limit.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_luminationtutor_mod_form extends moodleform_mod {
    /**
     * The form fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'tutorsettings', get_string('tutorsettings', 'mod_luminationtutor'));
        $mform->setExpanded('tutorsettings');
        $mform->addElement(
            'filemanager',
            'document',
            get_string('document', 'mod_luminationtutor'),
            null,
            tutor::file_options()
        );
        $mform->addHelpButton('document', 'document', 'mod_luminationtutor');
        $mform->addRule('document', null, 'required', null, 'client');

        $mform->addElement(
            'advcheckbox',
            'enabled',
            get_string('enabled', 'mod_luminationtutor'),
            get_string('enabled_label', 'mod_luminationtutor')
        );
        $mform->setDefault('enabled', 1);

        $mform->addElement('text', 'dailylimit', get_string('dailylimit', 'mod_luminationtutor'), ['size' => 4]);
        $mform->setType('dailylimit', PARAM_INT);
        $mform->setDefault('dailylimit', tutor::DEFAULT_DAILY_LIMIT);
        $mform->addHelpButton('dailylimit', 'dailylimit', 'mod_luminationtutor');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Load the current document into the file manager.
     *
     * @param array $defaultvalues Form defaults, by reference.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        $draftitemid = file_get_submitted_draft_itemid('document');
        // A new activity has no context yet, so there is nothing to load (item null).
        $itemid = $this->current->instance ? 0 : null;
        file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            'mod_luminationtutor',
            tutor::FILEAREA,
            $itemid,
            tutor::file_options()
        );
        $defaultvalues['document'] = $draftitemid;
    }

    /**
     * A document is needed and the daily limit must be at least 1.
     *
     * @param array $data Submitted data.
     * @param array $files Uploaded files.
     * @return array Errors by field.
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);
        $draftfiles = get_file_storage()->get_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            (int) ($data['document'] ?? 0),
            'id',
            false
        );
        if (!$draftfiles) {
            $errors['document'] = get_string('required');
        }
        if ((int) ($data['dailylimit'] ?? 0) < 1) {
            $errors['dailylimit'] = get_string('dailylimit_error', 'mod_luminationtutor');
        }
        return $errors;
    }
}
