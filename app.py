from flask import Flask, request
import subprocess
import os
import sys

app = Flask(__name__)

@app.route("/start_detection", methods=["POST"])
def start_detection():
    script_path = r"C:\xampp\htdocs\capstone\Detection\yolov5\detection2.py"
    
    # Use full path to python executable if needed, e.g., "C:/Users/YourUser/AppData/Local/Programs/Python/Python310/python.exe"
    python_exec = sys.executable

    try:
        subprocess.Popen([python_exec, script_path], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        return "Detection started", 200
    except Exception as e:
        return f"Error: {e}", 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
    