import json
import re

log_path = r'C:\Users\aliq3\.gemini\antigravity\brain\17815c2b-e9fa-4909-b2dc-e7e3f3383b9b\.system_generated\logs\transcript_full.jsonl'

with open(log_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        if data.get('type') == 'TOOL_RESPONSE' and data.get('content'):
            content = data['content']
            if 'C:/xampp/htdocs/point%20of%20sale/index.php' in content or 'C:\\xampp\\htdocs\\point of sale\\index.php' in content:
                with open('recovered_index.txt', 'a', encoding='utf-8') as out:
                    out.write(content + "\n\n=====\n\n")
            if 'style.css' in content:
                with open('recovered_style.txt', 'a', encoding='utf-8') as out:
                    out.write(content + "\n\n=====\n\n")
            if 'header.php' in content:
                with open('recovered_header.txt', 'a', encoding='utf-8') as out:
                    out.write(content + "\n\n=====\n\n")
