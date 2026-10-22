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
 * Backup task of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/luminationtutor/backup/moodle2/backup_luminationtutor_stepslib.php');

/**
 * Backup task of the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_luminationtutor_activity_task extends backup_activity_task {
    /**
     * No settings of its own.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * One step writes luminationtutor.xml.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new backup_luminationtutor_activity_structure_step('luminationtutor_structure', 'luminationtutor.xml'));
    }

    /**
     * Encode links to the activity's pages.
     *
     * @param string $content HTML that may contain links.
     * @return string The content with the links encoded.
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot . '/mod/luminationtutor/', '/');
        $content = preg_replace('/(' . $base . 'index.php\?id\=)([0-9]+)/', '$@LUMINATIONTUTORINDEX*$2@$', $content);
        return preg_replace('/(' . $base . 'view.php\?id\=)([0-9]+)/', '$@LUMINATIONTUTORVIEWBYID*$2@$', $content);
    }
}
