import os
import signal

pid = os.popen("ps -aux | grep -m 1 \"php keylogger_server.php\" | awk '{print $2}'").read()
os.kill(int(pid), signal.SIGINT)
