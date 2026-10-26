import json
import os
import sys

transcript_path = r"C:\Users\PC\.gemini\antigravity\brain\2c731605-92af-4c85-8847-98c09538b6b5\.system_generated\logs\transcript_full.jsonl"
output_md_path = r"docs\CONVERSATION_HISTORY_2026_10_20.md"
os.makedirs("docs", exist_ok=True)

print("Reading transcript...")

messages = []
with open(transcript_path, "r", encoding="utf-8") as f:
    for line in f:
        line = line.strip()
        if not line:
            continue
        try:
            entry = json.loads(line)
        except Exception:
            continue
        
        step_type = entry.get("type")
        source = entry.get("source")
        content = entry.get("content")
        created_at = entry.get("created_at", "")
        
        # User messages
        if step_type == "USER_INPUT" or source == "USER_EXPLICIT":
            # Clean content if enclosed in user tags
            text = str(content or "").strip()
            if text and not text.startswith("System:") and not text.startswith("<SYSTEM_MESSAGE>"):
                messages.append({
                    "role": "USER",
                    "time": created_at[:19].replace("T", " "),
                    "content": text
                })
        # Assistant final response (content)
        elif step_type == "PLANNER_RESPONSE" or source == "MODEL":
            text = str(content or "").strip()
            # If content is present (not just internal tool calls)
            if text and len(text) > 10:
                messages.append({
                    "role": "ASSISTANT",
                    "time": created_at[:19].replace("T", " "),
                    "content": text
                })

print(f"Extracted {len(messages)} messages.")

with open(output_md_path, "w", encoding="utf-8") as out:
    out.write("# سجل المحادثات والعمليات اليومية - KAYAN ERP\n\n")
    out.write(f"**تاريخ الاستخراج:** 2026-10-20\n")
    out.write(f"**معرف الجلسة (Conversation ID):** `2c731605-92af-4c85-8847-98c09538b6b5`\n\n")
    out.write("---\n\n")
    
    for m in messages:
        if m["role"] == "USER":
            out.write(f"### 👤 المستخدم (User) — *{m['time']}*\n\n")
            out.write(f"> {m['content']}\n\n")
        else:
            out.write(f"### 🤖 المساعد الذكي (Antigravity Assistant) — *{m['time']}*\n\n")
            out.write(f"{m['content']}\n\n")
        out.write("---\n\n")

print(f"Exported successfully to {output_md_path}!")
