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
 * English language strings for mod_luminationtutor.
 *
 * @package    mod_luminationtutor
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['askplaceholder'] = 'Ask a question about the course material';
$string['dailylimit'] = 'Questions per student per day';
$string['dailylimit_error'] = 'Enter a number of 1 or more.';
$string['dailylimit_help'] = 'Each question spends AI Tutor credits. When a student reaches this number, they can ask again the next day.';
$string['dailylimitreached'] = 'You have asked {$a} questions today, the daily limit. You can ask more tomorrow.';
$string['document'] = 'Course document';
$string['document_help'] = 'The PDF, Word (.docx) or text file the tutor answers from, for example the notes for this section. If you replace it, students\' conversations start again with the new document.';
$string['emptymessage'] = 'Type a question first.';
$string['enabled'] = 'Tutor';
$string['enabled_label'] = 'Students can ask the tutor questions';
$string['intro'] = 'Ask a question about this material. The tutor answers from the document your teacher added.';
$string['luminationtutor:addinstance'] = 'Add a Lumination AI Tutor';
$string['luminationtutor:use'] = 'Ask the Lumination AI Tutor questions';
$string['luminationtutor:view'] = 'View a Lumination AI Tutor';
$string['messagetoolong'] = 'Questions can be up to {$a} characters long.';
$string['modulename'] = 'Lumination AI Tutor';
$string['modulename_help'] = 'The Lumination AI Tutor lets students ask questions about a document you add, such as the notes for a section, and answers them from that document. Each student has their own conversation, and you choose how many questions each student can ask per day. Questions are answered by the AI Tutor API by Lumination AI. Students\' names and email addresses are not sent.';
$string['modulenameplural'] = 'Lumination AI Tutors';
$string['nodocument'] = 'The tutor has no course document yet.';
$string['nodocumentteacher'] = 'Add a course document in this activity\'s settings. The tutor answers from it.';
$string['noprovider'] = 'The Lumination AI Provider is not set up on this site. An administrator can add it under Site administration > AI > AI providers with an AI Tutor API key.';
$string['noreply'] = 'The AI Tutor API did not accept the question.';
$string['pluginadministration'] = 'Lumination AI Tutor administration';
$string['pluginname'] = 'Lumination AI Tutor';
$string['privacy:metadata:aitutor'] = 'Questions are sent to the AI Tutor API by Lumination AI, which answers them from the course document added by the teacher. The user\'s name and email address are not sent.';
$string['privacy:metadata:aitutor:conversationid'] = 'The ID of the user\'s conversation, so the tutor remembers earlier questions.';
$string['privacy:metadata:aitutor:message'] = 'The text of each question.';
$string['privacy:metadata:conv'] = 'Each user\'s conversation with the tutor of an activity.';
$string['privacy:metadata:conv:conversationid'] = 'The ID of the conversation in the AI Tutor API.';
$string['privacy:metadata:conv:timecreated'] = 'When the user first asked the tutor a question.';
$string['privacy:metadata:conv:userid'] = 'The ID of the user.';
$string['privacy:metadata:msg'] = 'The questions a user asked the tutor, and its replies.';
$string['privacy:metadata:msg:message'] = 'The question.';
$string['privacy:metadata:msg:reply'] = 'The tutor\'s reply.';
$string['privacy:metadata:msg:timecreated'] = 'When the question was asked.';
$string['privacy:metadata:msg:userid'] = 'The ID of the user who asked.';
$string['remaining'] = 'You can ask {$a} more today';
$string['replyfailed'] = 'The tutor could not answer this question. Please ask again.';
$string['send'] = 'Ask';
$string['thinking'] = 'The tutor is writing an answer...';
$string['tutornotavailable'] = 'The tutor is not available at the moment. Ask your teacher if this continues.';
$string['tutoroff'] = 'The tutor is switched off.';
$string['tutoroffteacher'] = 'The tutor is off. Students cannot ask questions until you switch it on in this activity\'s settings.';
$string['tutorsettings'] = 'Tutor';
$string['tutorunavailable'] = 'The tutor is not available right now. Please try again later.';
$string['usage'] = '{$a->messages} questions from {$a->students} students today, {$a->total} in total.';
$string['waitforreply'] = 'Wait for the answer to your last question before asking another.';
$string['yourquestion'] = 'Your question';
