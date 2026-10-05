from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text(encoding="utf-8")
text = text.replace("<text ", '<text font-weight="bold" ')
text = text.replace("<tspan ", '<tspan font-weight="bold" ')
path.write_text(text, encoding="utf-8")
print(f"bold applied: {path}")
