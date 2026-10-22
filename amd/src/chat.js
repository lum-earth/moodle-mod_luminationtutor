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
 * Chat of the Lumination AI Tutor activity.
 *
 * Shows the question at once, sends it (mod_luminationtutor_send_message) and polls
 * (mod_luminationtutor_get_reply) until the tutor has answered.
 *
 * @module     mod_luminationtutor/chat
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

/** @var {number} Milliseconds between checks for the reply. */
const POLL_INTERVAL = 3000;

/** @var {number} Checks before giving up (about ten minutes, like the server). */
const POLL_MAX = 200;

/**
 * Enable or disable the question box.
 *
 * @param {HTMLElement} root The chat.
 * @param {boolean} busy True while a reply is awaited.
 */
const setBusy = (root, busy) => {
    root.dataset.busy = busy ? '1' : '';
    const blocked = busy || root.dataset.limitreached === '1';
    root.querySelector('[data-region="input"]').disabled = blocked;
    root.querySelector('[data-region="send"]').disabled = blocked;
};

/**
 * Scroll the conversation to the latest message.
 *
 * @param {HTMLElement} root The chat.
 */
const scrollToEnd = (root) => {
    const history = root.querySelector('[data-region="history"]');
    history.scrollTop = history.scrollHeight;
};

/**
 * Show the outcome of a message.
 *
 * @param {HTMLElement} root The chat.
 * @param {HTMLElement} message The message element.
 * @param {string} status completed or failed.
 * @param {string} replyhtml The reply as HTML (cleaned by the server).
 */
const showReply = async(root, message, status, replyhtml) => {
    const reply = message.querySelector('[data-region="reply"]');
    if (status === 'completed') {
        Templates.replaceNodeContents(reply, replyhtml, '');
    } else {
        const span = document.createElement('span');
        span.className = 'text-danger';
        span.textContent = await getString('replyfailed', 'mod_luminationtutor');
        reply.replaceChildren(span);
    }
    delete message.dataset.pending;
    setBusy(root, false);
    scrollToEnd(root);
};

/**
 * Check for the reply to a message until it arrives.
 *
 * @param {HTMLElement} root The chat.
 * @param {HTMLElement} message The message element, with data-messageid.
 * @param {number} attempt Checks so far.
 */
const poll = (root, message, attempt) => {
    setTimeout(async() => {
        let result;
        try {
            result = await Ajax.call([{
                methodname: 'mod_luminationtutor_get_reply',
                args: {messageid: parseInt(message.dataset.messageid, 10)},
            }])[0];
        } catch (error) {
            await showReply(root, message, 'failed', '');
            Notification.exception(error);
            return;
        }
        if (result.status === 'pending' && attempt < POLL_MAX) {
            poll(root, message, attempt + 1);
            return;
        }
        await showReply(root, message, result.status, result.replyhtml);
    }, POLL_INTERVAL);
};

/**
 * A question waiting for its reply, built here (same markup as templates/message.mustache)
 * so that it shows the moment it is sent.
 *
 * @param {string} text The question.
 * @returns {HTMLElement} The message element.
 */
const buildPendingMessage = (text) => {
    const message = document.createElement('div');
    message.className = 'mod_luminationtutor-message mb-2';
    message.dataset.region = 'message';
    message.dataset.messageid = '0';
    message.dataset.pending = '1';

    const question = document.createElement('div');
    question.className = 'mod_luminationtutor-question p-2 mb-1 rounded';
    question.textContent = text;

    const reply = document.createElement('div');
    reply.className = 'mod_luminationtutor-reply p-2 rounded';
    reply.dataset.region = 'reply';
    const thinking = document.createElement('span');
    thinking.className = 'text-muted';
    getString('thinking', 'mod_luminationtutor').then((str) => {
        thinking.textContent = str;
        return str;
    }).catch(Notification.exception);
    reply.append(thinking);

    message.append(question, reply);
    return message;
};

/**
 * Show the question at once, send it and wait for the reply.
 *
 * @param {HTMLElement} root The chat.
 */
const send = async(root) => {
    const input = root.querySelector('[data-region="input"]');
    const text = input.value.trim();
    if (text === '' || root.dataset.busy === '1') {
        return;
    }
    setBusy(root, true);
    root.querySelector('[data-region="intro"]').classList.add('d-none');

    const message = buildPendingMessage(text);
    root.querySelector('[data-region="history"]').append(message);
    input.value = '';
    scrollToEnd(root);

    try {
        const result = await Ajax.call([{
            methodname: 'mod_luminationtutor_send_message',
            args: {cmid: parseInt(root.dataset.cmid, 10), message: text},
        }])[0];
        message.dataset.messageid = result.id;
        root.querySelector('[data-region="remaining"]').textContent = result.remainingtext;
        root.dataset.limitreached = result.remaining > 0 ? '' : '1';
        poll(root, message, 0);
    } catch (error) {
        // Not sent: take the question back so the student can try again.
        message.remove();
        input.value = text;
        setBusy(root, false);
        Notification.exception(error);
    }
};

/**
 * Start the chat of an activity.
 *
 * @param {number} cmid Course module id.
 */
export const init = (cmid) => {
    const root = document.querySelector(
        `[data-region="mod_luminationtutor-chat"][data-cmid="${cmid}"]`
    );
    if (!root || root.dataset.initialised === '1') {
        return;
    }
    root.dataset.initialised = '1';
    // Load the strings now, so a question shows complete the moment it is sent.
    getString('thinking', 'mod_luminationtutor').catch(Notification.exception);
    getString('replyfailed', 'mod_luminationtutor').catch(Notification.exception);
    const input = root.querySelector('[data-region="input"]');
    root.dataset.limitreached = input.disabled ? '1' : '';

    root.querySelector('[data-region="form"]').addEventListener('submit', (e) => {
        e.preventDefault();
        send(root);
    });
    input.addEventListener('keydown', (e) => {
        // Enter sends, Shift+Enter starts a new line.
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send(root);
        }
    });

    // Replies still being written when the page was loaded.
    root.querySelectorAll('[data-region="message"][data-pending="1"]').forEach((message) => {
        setBusy(root, true);
        poll(root, message, 0);
    });
    scrollToEnd(root);
};
