# Keylogger

Python keylogger for learning purposes that stores collected data from infected machines in a specified IP.

## Requirements

```
Python installed in infected machine.
PHP installed in server machine.
```

## Server usage

```
# launches the server php file that collects data
python autorun.py

# sends responses to stop collecting data 
python data_collect_stop.py

# ends server endpoint
python server_keylogger_stop.py
```

## Infected machine usage

```
python keylogger.py
```

## Issues that may occur

Server machine may be blocking requests due to firewall policy, it is necessary to open the specified port in server machine for TCP connections.

## Notes

In the future may be implemented a way to autorun the keylogger.py file in the infected machine without using python/terminal.