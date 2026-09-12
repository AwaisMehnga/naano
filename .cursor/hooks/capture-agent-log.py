#!/usr/bin/env python3
"""Append Cursor prompt/response captures to .agent-logs/. Fail open."""

from __future__ import annotations

import fcntl
import json
import os
import re
import sys
import traceback
from datetime import datetime, timezone
from pathlib import Path

AUTHOR = "AwaisMehnga"
PROJECT = "naano"
TOOL = "cursor"
LOG_DIR_NAME = ".agent-logs"
INDEX_NAME = ".session-index.json"
ERROR_LOG_NAME = "capture-errors.log"

ENTRY_RE = re.compile(
    r"^\[LOG_ENTRY type=(PROMPT|RESPONSE) num=(\d+) session=([^\]]+)\]\s*$",
    re.MULTILINE,
)


def utc_now() -> datetime:
    return datetime.now(timezone.utc)


def utc_stamp(moment: datetime) -> str:
    return moment.strftime("%Y-%m-%dT%H:%M:%S.") + f"{moment.microsecond // 1000:03d}Z"


def short_id(session_id: str) -> str:
    return session_id.split("-")[0][:8] if session_id else "unknown"


def repo_root(payload: dict) -> Path:
    for key in ("CURSOR_PROJECT_DIR", "CLAUDE_PROJECT_DIR"):
        value = os.environ.get(key)
        if value:
            return Path(value)
    roots = payload.get("workspace_roots")
    if isinstance(roots, list) and roots and isinstance(roots[0], str) and roots[0].strip():
        return Path(roots[0])
    return Path.cwd()


def hooks_dir() -> Path:
    return Path(__file__).resolve().parent


def log_error(message: str) -> None:
    try:
        path = hooks_dir() / ERROR_LOG_NAME
        with path.open("a", encoding="utf-8") as handle:
            handle.write(f"{utc_stamp(utc_now())} {message.rstrip()}\n")
    except OSError:
        pass


def read_stdin() -> dict:
    raw = sys.stdin.read()
    if not raw.strip():
        return {}
    data = json.loads(raw)
    return data if isinstance(data, dict) else {}


def event_name(payload: dict) -> str:
    name = str(payload.get("hook_event_name") or "").strip()
    if name:
        return name
    if "prompt" in payload:
        return "beforeSubmitPrompt"
    if "status" in payload and "loop_count" in payload:
        return "stop"
    if "text" in payload and "duration_ms" not in payload:
        return "afterAgentResponse"
    return ""


def session_id_of(payload: dict) -> str:
    for key in ("conversation_id", "session_id"):
        value = payload.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()
    return ""


def model_of(payload: dict) -> str:
    for key in ("model", "model_id"):
        value = payload.get(key)
        if isinstance(value, str) and value.strip():
            return value.strip()
    return "unknown"


def load_index(lock_dir: Path) -> dict:
    path = lock_dir / INDEX_NAME
    if not path.exists():
        return {}
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
        return data if isinstance(data, dict) else {}
    except (OSError, json.JSONDecodeError):
        return {}


def save_index(lock_dir: Path, index: dict) -> None:
    path = lock_dir / INDEX_NAME
    path.write_text(json.dumps(index, indent=2, sort_keys=True) + "\n", encoding="utf-8")


def find_existing_log(logs_dir: Path, session_id: str) -> Path | None:
    if not logs_dir.exists():
        return None
    needle = f"session_id: {session_id}"
    for path in sorted(logs_dir.glob("*.md")):
        try:
            if needle in path.read_text(encoding="utf-8")[:800]:
                return path
        except OSError:
            continue
    return None


def resolve_log_path(logs_dir: Path, lock_dir: Path, session_id: str, moment: datetime) -> Path:
    index = load_index(lock_dir)
    recorded = index.get(session_id)
    if isinstance(recorded, str):
        candidate = logs_dir / recorded
        if candidate.exists():
            return candidate
    existing = find_existing_log(logs_dir, session_id)
    if existing is not None:
        index[session_id] = existing.name
        save_index(lock_dir, index)
        return existing
    filename = f"{moment.strftime('%Y-%m-%d_%H-%M-%S')}_{session_id}.md"
    index[session_id] = filename
    save_index(lock_dir, index)
    return logs_dir / filename


def parse_frontmatter(text: str) -> tuple[dict[str, str], str]:
    if not text.startswith("---\n"):
        return {}, text
    end = text.find("\n---\n", 4)
    if end == -1:
        return {}, text
    block = text[4:end]
    body = text[end + 5 :]
    meta: dict[str, str] = {}
    for line in block.splitlines():
        if ":" not in line:
            continue
        key, value = line.split(":", 1)
        meta[key.strip()] = value.strip()
    return meta, body


def render_frontmatter(meta: dict[str, str], session_id: str, date: str, model: str) -> str:
    header = "\n".join(
        [
            "---",
            f"session_id: {session_id}",
            f"date: {date}",
            f"author: {AUTHOR}",
            f"model: {model}",
            f"tool: {TOOL}",
            f"project: {PROJECT}",
            f"total_exchanges: {meta.get('total_exchanges', '0')}",
            f"first_prompt_time: {meta.get('first_prompt_time', '')}",
            f"last_prompt_time: {meta.get('last_prompt_time', '')}",
            "---",
        ]
    )
    return header + "\n"


def session_intro(session_id: str, date: str) -> str:
    return (
        f"\n# Session Log - {date}\n\n"
        f"Session: `{short_id(session_id)}` | Project: `{PROJECT}` | Author: `{AUTHOR}`\n\n"
        "---\n\n"
    )


def entry_starts(text: str) -> list[re.Match[str]]:
    return list(ENTRY_RE.finditer(text))


def max_prompt_num(text: str) -> int:
    highest = 0
    for match in entry_starts(text):
        if match.group(1) == "PROMPT":
            highest = max(highest, int(match.group(2)))
    return highest


def replace_or_append_entry(text: str, kind: str, num: int, session_id: str, block: str) -> str:
    matches = entry_starts(text)
    for index, match in enumerate(matches):
        if match.group(1) != kind or int(match.group(2)) != num:
            continue
        end = matches[index + 1].start() if index + 1 < len(matches) else len(text)
        return text[: match.start()] + block + text[end:].lstrip("\n")
    if text and not text.endswith("\n"):
        text += "\n"
    if text and not text.endswith("\n\n"):
        text += "\n"
    return text + block


def has_response(text: str, num: int) -> bool:
    for match in entry_starts(text):
        if match.group(1) == "RESPONSE" and int(match.group(2)) == num:
            return True
    return False


def format_entry(kind: str, num: int, session_id: str, timestamp: str, model: str, body: str) -> str:
    return (
        f"[LOG_ENTRY type={kind} num={num} session={short_id(session_id)}]\n"
        f"timestamp: {timestamp}\n"
        f"model: {model}\n\n"
        f"{body.rstrip()}\n\n"
    )


def ensure_header(text: str, session_id: str, moment: datetime, model: str) -> str:
    if text.strip():
        return text
    date = moment.strftime("%Y-%m-%d")
    return render_frontmatter({}, session_id, date, model) + session_intro(session_id, date)


def update_meta(text: str, session_id: str, model: str, prompt_time: str | None, exchanges: int) -> str:
    meta, body = parse_frontmatter(text)
    if prompt_time:
        meta["first_prompt_time"] = meta.get("first_prompt_time") or prompt_time
        meta["last_prompt_time"] = prompt_time
    meta["total_exchanges"] = str(exchanges)
    date = meta.get("date") or utc_now().strftime("%Y-%m-%d")
    if "Session Log" not in body:
        body = session_intro(session_id, date) + body.lstrip()
    return render_frontmatter(meta, session_id, date, model) + body.lstrip("\n")


def extract_transcript_text(transcript_path: str) -> str:
    path = Path(transcript_path)
    if not path.is_file():
        return ""
    last_text = ""
    try:
        for line in path.read_text(encoding="utf-8").splitlines():
            if not line.strip():
                continue
            try:
                row = json.loads(line)
            except json.JSONDecodeError:
                continue
            if row.get("role") != "assistant":
                continue
            message = row.get("message") or {}
            content = message.get("content") if isinstance(message, dict) else None
            if not isinstance(content, list):
                continue
            chunks = [
                part.get("text", "")
                for part in content
                if isinstance(part, dict) and part.get("type") == "text" and part.get("text")
            ]
            combined = "\n\n".join(chunk for chunk in chunks if chunk.strip())
            if combined.strip():
                last_text = combined
    except OSError:
        return ""
    return last_text


def write_prompt(log_path: Path, session_id: str, model: str, prompt: str, moment: datetime) -> None:
    timestamp = utc_stamp(moment)
    text = log_path.read_text(encoding="utf-8") if log_path.exists() else ""
    text = ensure_header(text, session_id, moment, model)
    num = max_prompt_num(text) + 1
    block = format_entry("PROMPT", num, session_id, timestamp, model, prompt)
    text = replace_or_append_entry(text, "PROMPT", num, session_id, block)
    text = update_meta(text, session_id, model, timestamp, num)
    log_path.write_text(text, encoding="utf-8")


def write_response(log_path: Path, session_id: str, model: str, response: str, moment: datetime) -> None:
    if not log_path.exists():
        return
    text = log_path.read_text(encoding="utf-8")
    num = max_prompt_num(text)
    if num == 0:
        return
    timestamp = utc_stamp(moment)
    block = format_entry("RESPONSE", num, session_id, timestamp, model, response)
    text = replace_or_append_entry(text, "RESPONSE", num, session_id, block)
    text = update_meta(text, session_id, model, None, num)
    log_path.write_text(text, encoding="utf-8")


def handle(payload: dict) -> dict:
    name = event_name(payload)
    session_id = session_id_of(payload)
    if not session_id:
        return {"continue": True} if name == "beforeSubmitPrompt" else {}

    moment = utc_now()
    model = model_of(payload)
    root = repo_root(payload)
    logs_dir = root / LOG_DIR_NAME
    logs_dir.mkdir(parents=True, exist_ok=True)
    lock_dir = logs_dir
    lock_path = lock_dir / ".capture.lock"

    with lock_path.open("a+", encoding="utf-8") as lock:
        fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
        log_path = resolve_log_path(logs_dir, lock_dir, session_id, moment)

        if name == "beforeSubmitPrompt":
            prompt = payload.get("prompt")
            if isinstance(prompt, str):
                write_prompt(log_path, session_id, model, prompt, moment)
            return {"continue": True}

        if name == "afterAgentResponse":
            text = payload.get("text")
            if isinstance(text, str) and text.strip():
                write_response(log_path, session_id, model, text, moment)
            return {}

        if name == "stop":
            if log_path.exists():
                current = log_path.read_text(encoding="utf-8")
                num = max_prompt_num(current)
                if num and not has_response(current, num):
                    transcript = payload.get("transcript_path") or os.environ.get("CURSOR_TRANSCRIPT_PATH")
                    extracted = extract_transcript_text(transcript) if isinstance(transcript, str) else ""
                    if extracted.strip():
                        write_response(log_path, session_id, model, extracted, moment)
            return {}

    return {}


def main() -> int:
    try:
        payload = read_stdin()
        result = handle(payload)
        sys.stdout.write(json.dumps(result))
    except Exception:
        log_error(traceback.format_exc())
        event = ""
        try:
            event = event_name(payload)  # type: ignore[name-defined]
        except Exception:
            pass
        if event == "beforeSubmitPrompt":
            sys.stdout.write('{"continue": true}')
        else:
            sys.stdout.write("{}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
