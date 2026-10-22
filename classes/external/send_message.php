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
 * External function that sends a question to the tutor.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_luminationtutor\external;

use aiprovider_lumination\api_client;
use aiprovider_lumination\api_exception;
use mod_luminationtutor\tutor;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function that sends a question to the tutor.
 *
 * Returns at once with the message id; the chat then polls get_reply for the answer.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_message extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters The parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            // Plain text, sent to the API as typed and always shown escaped.
            'message' => new external_value(PARAM_RAW, 'The question'),
        ]);
    }

    /**
     * Send a question to the tutor.
     *
     * @param int $cmid Course module id.
     * @param string $message The question.
     * @return array The message id and the messages left today.
     */
    public static function execute(int $cmid, string $message): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'message' => $message,
        ]);
        $tutor = tutor::from_cmid($params['cmid']);
        $context = $tutor->get_context();
        self::validate_context($context);
        require_capability('mod/luminationtutor:use', $context);

        try {
            $msg = $tutor->send((int) $USER->id, $params['message'], api_client::for_site());
        } catch (api_exception $e) {
            // Students get a plain message; the API detail is debug info for administrators.
            throw new \moodle_exception('tutorunavailable', 'mod_luminationtutor', '', null, $e->getMessage());
        }

        $remaining = $tutor->remaining_today((int) $USER->id);
        return [
            'id' => (int) $msg->id,
            'remaining' => $remaining,
            'remainingtext' => get_string('remaining', 'mod_luminationtutor', $remaining),
        ];
    }

    /**
     * Return value.
     *
     * @return external_single_structure The structure.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Message id, to poll with get_reply'),
            'remaining' => new external_value(PARAM_INT, 'Messages the user can still send today'),
            'remainingtext' => new external_value(PARAM_TEXT, 'The same, as text for the chat'),
        ]);
    }
}
