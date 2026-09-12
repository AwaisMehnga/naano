# CAPTURE-TEST

## Tool and model

- **Tool:** Cursor (Agent chat)
- **Model (this machine):** Cursor Grok 4.6 — same model plans and executes
- **Slugs written by the hook:**
  - Session 1: `cursor-grok-4.6-high-fast`
  - Session 2: `cursor-grok-4.6-high`

## Mechanism

Cursor native project hooks. They fire on their own from `.cursor/hooks.json` (no manual log command).

- `beforeSubmitPrompt` → write the user prompt
- `afterAgentResponse` → write the final assistant text for that turn (overwrites if the turn emits more than one assistant message, so the last one wins)
- `stop` → fallback only: if a prompt has no response yet, try `transcript_path`

Script: `.cursor/hooks/capture-agent-log.py`

Config changed: `.cursor/hooks.json`

Looked up before installing: [Cursor Hooks docs](https://cursor.com/docs/hooks) and the Cursor `create-hook` skill. Confirmed Cursor also writes session transcripts under `~/.cursor/projects/.../agent-transcripts/`; that is a fallback, not the capture path.

## Log files the canaries landed in

- Session 1: `.agent-logs/2026-09-12_12-47-30_a0b2da8e-6e92-43da-985b-53781c4257d4.md`
- Session 2: `.agent-logs/2026-09-12_12-47-49_0412c52a-3793-494f-b224-6d6dd807d9c4.md`

`.agent-logs/` is not in `.gitignore`.

## Canary entries (raw)

### Session 1

[LOG_ENTRY type=PROMPT num=1 session=a0b2da8e]
timestamp: 2026-09-12T12:47:30.843Z
model: cursor-grok-4.6-high-fast

CAPTURE TEST — 8x assignment, Awais

[LOG_ENTRY type=RESPONSE num=1 session=a0b2da8e]
timestamp: 2026-09-12T12:47:40.281Z
model: cursor-grok-4.6-high-fast

Session 1 canary **prompt** is in the log.

File: `.agent-logs/2026-09-12_12-47-30_a0b2da8e-6e92-43da-985b-53781c4257d4.md`

```
[LOG_ENTRY type=PROMPT num=1 session=a0b2da8e]
timestamp: 2026-09-12T12:47:30.843Z
model: cursor-grok-4.6-high-fast

CAPTURE TEST — 8x assignment, Awais
```

The hook ran from project config (`beforeSubmitPrompt` → `capture-agent-log.py`, `continue: true`). This reply should land as `RESPONSE num=1` when the turn ends.

Session 2 still needs a resend. Its earlier canary ran before the config loaded. In that other chat, send:

`CAPTURE TEST — 8x assignment, Awais, session 2`

Once that prompt and response are in `.agent-logs/` as well, I will write `CAPTURE-TEST.md`.

### Session 2

[LOG_ENTRY type=PROMPT num=1 session=0412c52a]
timestamp: 2026-09-12T12:47:49.400Z
model: cursor-grok-4.6-high

CAPTURE TEST — 8x assignment, Awais, session 2

[LOG_ENTRY type=RESPONSE num=1 session=0412c52a]
timestamp: 2026-09-12T12:48:27.282Z
model: cursor-grok-4.6-high

Session 2 canary **prompt** is in the log.

File: `.agent-logs/2026-09-12_12-47-49_0412c52a-3793-494f-b224-6d6dd807d9c4.md`

```
[LOG_ENTRY type=PROMPT num=1 session=0412c52a]
timestamp: 2026-09-12T12:47:49.400Z
model: cursor-grok-4.6-high

CAPTURE TEST — 8x assignment, Awais, session 2
```

The project hook ran in this chat too (`beforeSubmitPrompt` → `capture-agent-log.py`). This reply should land as `RESPONSE num=1` when the turn ends.

Session 1 already has both its prompt and response in `.agent-logs/2026-09-12_12-47-30_a0b2da8e-6e92-43da-985b-53781c4257d4.md`. After this response is written, both sessions have captured canaries, which is the step 4 check. Next message in either chat can write `CAPTURE-TEST.md`.

## What did not work first

1. **`loop_limit: 0` on `stop`.** Cursor logged `Invalid project config: stop[0]: Hook script loop_limit must be a positive integer (use null for no limit)` and then `Failed to parse project hooks configuration`. The whole project hook file was ignored. The first canary (`CAPTURE TEST — 8x assignment, Awais` at 12:46:02Z) and an earlier session-2 attempt (`66b28189-…`) only hit the ponytail plugin hook. Fixed by removing `loop_limit`. Cursor then logged `Loaded 3 project hook(s) for steps: beforeSubmitPrompt, afterAgentResponse, stop`. Both canaries were resent after that reload.

2. **Did not use a project rule as the logger.** Cursor’s assignment hint mentioned a rules file plus history export. A rule that asks the model to remember to log is not automatic. Native hooks are the mechanism that fires on its own.

3. **Sandbox-owned directories.** An early dry-run left `.agent-logs/` and `.cursor/` as `root:root`. Ownership was corrected to the workspace user before the successful canaries so the hook could write.
