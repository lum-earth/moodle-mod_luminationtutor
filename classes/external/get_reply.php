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
 * External function that checks whether the tutor has answered a question.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_luminationtutor\external;

use aiprovider_lumination\api_client;
use mod_luminationtutor\tutor;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function that checks whether the tutor has answered a question.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_reply extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters The parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'messageid' => new external_value(PARAM_INT, 'Message id returned by send_message'),
        ]);
    }

    /**
     * Check for the reply to one of the current user's messages.
     *
     * @param int $messageid Message id.
     * @return array Status and, once completed, the reply as HTML.
     */
    public static function execute(int $messageid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['messageid' => $messageid]);
        // Users only ever see their own messages.
        $msg = $DB->get_record(
            'luminationtutor_msg',
            ['id' => $params['messageid'], 'userid' => $USER->id],
            '*',
            MUST_EXIST
        );
        $tutor = tutor::from_instance_id((int) $msg->luminationtutorid);
        $context = $tutor->get_context();
        self::validate_context($context);
        require_capability('mod/luminationtutor:use', $context);

        $msg = $tutor->check_reply($msg, api_client::for_site());
        return [
            'status' => $msg->status,
            'replyhtml' => $tutor->format_reply($msg),
        ];
    }

    /**
     * Return value.
     *
     * @return external_single_structure The structure.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'pending, completed or failed'),
            'replyhtml' => new external_value(PARAM_RAW, 'The reply as HTML, empty until completed'),
        ]);
    }
}
