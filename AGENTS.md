# Lumination AI Tutor for Moodle (mod_luminationtutor)

Rules for coding agents in this repository. Keep this file short and true; update a fact in the same change that makes it untrue.

## What this is

A Moodle 5.0+ activity module. Teachers add it to a course section with one source document (file area `document` in the module context); students chat with the AI Tutor API about it on `view.php`. `classes/tutor.php` holds the logic: settings, one conversation per user and activity (`luminationtutor_conv`), messages and replies (`luminationtutor_msg`), daily limit, usage. The chat (`amd/src/chat.js`, templates `chat` and `message`) calls two external functions: `send_message` (submits, returns at once) and `get_reply` (polls). `lib.php` has the activity callbacks; `backup/moodle2` backs up settings and the document, and conversations only with user data.

It requires `lum-earth/moodle-aiprovider_lumination` (the hub): the API key and client come from `\aiprovider_lumination\api_client::for_site()`. CI installs it with `moodle-plugin-ci add-plugin`.

- `/tutor` is stateful. `POST /tutor {message, conversation_id?, file_b64?, file_name?}` returns `{request_id, conversation_id}` (no `status`); `GET /requests/{id}` returns `status` and, when completed, `result.reply` (Markdown) and `result.title`. The file goes with a conversation's first message only. Checked against the real API on 22 Oct 2026; `tests/fixtures/fake_api_client.php` mirrors it.
- Never send the student's name, email or other profile data to the API (a test checks this).
- An earlier block version (`block_luminationtutor`) was never released; this module replaced it.

## Checks

CI (`.github/workflows/ci.yml`, moodle-plugin-ci) must pass on every supported branch: lint, code checker and PHPDoc with zero warnings, validate, mustache, grunt, PHPUnit. Local dev: `docker-compose.yml` in the parent workspace runs Moodle 5.2 on port 8081 with this folder mounted at `public/mod/luminationtutor`.

- `amd/build` must match `amd/src`. Build with Moodle's grunt (`npx grunt amd`) from a copy of the plugin inside a Moodle checkout that has `npm ci` run, then copy `amd/build` back.

## Moodle coding rules that CI enforces

- `defined('MOODLE_INTERNAL') || die();` only in files with side effects (`version.php`, `db/*.php`, `mod_form.php`, backup task files, tests that `require_once`). Never in class files, `lib.php` or pages that load `config.php`.
- Multi-line `if` conditions: first expression on the line after `(`, closing `)` on its own line. Multi-line calls: one argument per line.
- Test classes are `final`. `else if`, not `elseif`. Inline comments end with `.`, `!` or `?`. Lines up to 132 characters (lang files excepted).
- No empty `catch` blocks; no `debugging()` in code that tests run.
- Lang files are plain sorted `$string[...] = '...';` lines. Keep `lang/es` in step with `lang/en`.

## Boundaries

- Never push, move or delete a Git tag, and never publish a release; the maintainer does that.
- Ask before calls to the real AI Tutor API (they spend credits) and before any other networked side effect.
- Never commit a key, a password or a personal path.
- Listing names and descriptions follow the Marketplace guide in the maintainer's workspace (`docs/marketplace-listing-guide.md`).
