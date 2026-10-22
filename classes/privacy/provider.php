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
 * Privacy provider for the Lumination AI Tutor activity.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_luminationtutor\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the Lumination AI Tutor activity.
 *
 * Stores each user's conversation (the AI Tutor conversation id) and messages with their
 * replies, in the activity context. Messages and the course document are sent to the AI Tutor
 * API; the user's name and email are not.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('luminationtutor_conv', [
            'userid' => 'privacy:metadata:conv:userid',
            'conversationid' => 'privacy:metadata:conv:conversationid',
            'timecreated' => 'privacy:metadata:conv:timecreated',
        ], 'privacy:metadata:conv');
        $collection->add_database_table('luminationtutor_msg', [
            'userid' => 'privacy:metadata:msg:userid',
            'message' => 'privacy:metadata:msg:message',
            'reply' => 'privacy:metadata:msg:reply',
            'timecreated' => 'privacy:metadata:msg:timecreated',
        ], 'privacy:metadata:msg');
        $collection->add_external_location_link('aitutor', [
            'message' => 'privacy:metadata:aitutor:message',
            'conversationid' => 'privacy:metadata:aitutor:conversationid',
        ], 'privacy:metadata:aitutor');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT ctx.id
               FROM {context} ctx
               JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
               JOIN {modules} m ON m.id = cm.module AND m.name = :modname
               JOIN {luminationtutor_conv} c ON c.luminationtutorid = cm.instance
              WHERE c.userid = :userid',
            ['contextlevel' => CONTEXT_MODULE, 'modname' => 'luminationtutor', 'userid' => $userid]
        );
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist) {
        $instanceid = self::get_instance_id($userlist->get_context());
        if (!$instanceid) {
            return;
        }
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {luminationtutor_conv} WHERE luminationtutorid = :instanceid',
            ['instanceid' => $instanceid]
        );
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = self::get_instance_id($context);
            if (!$instanceid) {
                continue;
            }
            $conv = $DB->get_record('luminationtutor_conv', ['luminationtutorid' => $instanceid, 'userid' => $userid]);
            if (!$conv) {
                continue;
            }
            $messages = [];
            $records = $DB->get_records(
                'luminationtutor_msg',
                ['luminationtutorid' => $instanceid, 'userid' => $userid],
                'timecreated, id'
            );
            foreach ($records as $msg) {
                $messages[] = (object) [
                    'message' => $msg->message,
                    'reply' => $msg->reply,
                    'status' => $msg->status,
                    'timecreated' => transform::datetime($msg->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'mod_luminationtutor')],
                (object) [
                    'conversationid' => $conv->conversationid,
                    'timecreated' => transform::datetime($conv->timecreated),
                    'messages' => $messages,
                ]
            );
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $instanceid = self::get_instance_id($context);
        if (!$instanceid) {
            return;
        }
        $DB->delete_records('luminationtutor_msg', ['luminationtutorid' => $instanceid]);
        $DB->delete_records('luminationtutor_conv', ['luminationtutorid' => $instanceid]);
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $instanceid = self::get_instance_id($userlist->get_context());
        $userids = $userlist->get_userids();
        if (!$instanceid || !$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['instanceid'] = $instanceid;
        $select = "luminationtutorid = :instanceid AND userid {$insql}";
        $DB->delete_records_select('luminationtutor_msg', $select, $params);
        $DB->delete_records_select('luminationtutor_conv', $select, $params);
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = self::get_instance_id($context);
            if (!$instanceid) {
                continue;
            }
            $conditions = ['luminationtutorid' => $instanceid, 'userid' => $userid];
            $DB->delete_records('luminationtutor_msg', $conditions);
            $DB->delete_records('luminationtutor_conv', $conditions);
        }
    }

    /**
     * The luminationtutor id of an activity context.
     *
     * @param \context $context A context.
     * @return int|null The id, or null if the context is not a Lumination AI Tutor activity.
     */
    private static function get_instance_id(\context $context): ?int {
        if (!$context instanceof \context_module) {
            return null;
        }
        $cm = get_coursemodule_from_id('luminationtutor', $context->instanceid);
        return $cm ? (int) $cm->instance : null;
    }
}
