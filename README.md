# Lumination AI Tutor for Moodle

An AI tutor activity for Moodle courses. Students ask questions in a chat and get answers from the course's own material, a document the teacher adds. Part of the Lumination AI plugins for Moodle.

## How it works

1. A teacher adds a **Lumination AI Tutor** activity to a course section, like any other activity.
2. In its settings, the teacher adds the document the tutor answers from (PDF, DOCX or TXT), for example that section's notes. They can switch the tutor off and set how many questions each student can ask per day (20 by default).
3. Students open the activity and type a question. Their question shows at once and the answer follows, usually within 10 to 20 seconds.
4. Each student has their own conversation, so the tutor remembers their earlier questions. If the teacher replaces the document, conversations start again with the new one.
5. Teachers see how many questions students asked today and in total.

Add one activity per section or topic to give each its own material. Activity completion (on view), logs, and backup and restore work like other activities. Backups include students' conversations only when user data is included.

Questions and the document are sent to the AI Tutor API. Students' names and email addresses are not.

## Requirements

- Moodle 5.0 or later
- The Lumination AI Provider (`aiprovider_lumination`), set up with an AI Tutor API key under **Site administration > AI > AI providers**. It holds the key for all Lumination AI plugins.

Each question spends AI Tutor credits. New AI Tutor accounts get $20 of free credits. Register at https://ai-tutor.ai and create a key at https://ai-tutor.ai/dashboard/api.

## Other Lumination AI plugins

- Lumination AI Provider, the hub that holds the API key: https://github.com/lum-earth/moodle-aiprovider_lumination
- Lumination AI Course Generator: https://github.com/lum-earth/moodle-local_lumination
- Lumination AI Question Generator: https://github.com/lum-earth/moodle-qbank_lumination

## License

GNU GPL v3 or later, https://www.gnu.org/copyleft/gpl.html

## Support

Report bugs at https://github.com/lum-earth/moodle-mod_luminationtutor/issues or write to hello@ai-tutor.ai.
