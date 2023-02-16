# Keylogger

Python keylogger for learning purposes that stores collected data from infected machines in a specified IP.

## Requirements

```
PHP installed on server machine.
Python installed on infected machine.
```

## Server usage

On server folder

```
# launches the server php file that collects data
python autorun.py

# sends messages to infected machines to stop collecting data
python data_collect_stop.py

# ends server endpoint
python server_keylogger_stop.py
```

## Infected machine usage

On client folder

```
python keylogger.py
```

## Issues that may occur

Server machine may be blocking requests due to firewall policy, it is necessary to open the specified port in server machine for TCP connections.