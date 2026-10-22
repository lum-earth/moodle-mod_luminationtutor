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
 * External functions of the Lumination AI Tutor, called by the chat (amd/src/chat.js).
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_luminationtutor_send_message' => [
        'classname' => \mod_luminationtutor\external\send_message::class,
        'description' => 'Send a question to the AI tutor of an activity.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/luminationtutor:use',
    ],
    'mod_luminationtutor_get_reply' => [
        'classname' => \mod_luminationtutor\external\get_reply::class,
        'description' => 'Check whether the tutor has answered a question.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/luminationtutor:use',
    ],
];
