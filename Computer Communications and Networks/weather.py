#!/usr/bin/env python3

# Tomáš Zálešák
# xzales13@stud.fit.vutbr.cz

import socket   # socket communication
import json     # parsing json data to dict
import sys      # sys.exit() function
import re       # splitting response data

# 2 arguments
if len(sys.argv) != 3:
    print('wrong number of arguments')
    sys.exit(0)

city = sys.argv[2].lower()
app_id = sys.argv[1]

HOST = 'api.openweathermap.org'
PATH = '/data/2.5/weather?q=' + city + '&APPID=' + app_id + '&units=metric'
PORT = 80
request = 'GET ' + PATH + ' HTTP/1.1\nHost: ' + HOST + '\n\n'

s = None

# try to connect, support for both IPv4 and IPv6
for res in socket.getaddrinfo(HOST, PORT, socket.AF_UNSPEC, socket.SOCK_STREAM):
    addrfamily, socktype, proto, canonname, sockaddr = res
    try:
        s = socket.socket(addrfamily, socktype, proto)
    except OSError as msg:
        s = None
        continue
    try:
        s.connect(sockaddr)
    except OSError as msg:
        s.close()
        s = None
        continue
    break

if s is None:
    print('could not open socket')
    sys.exit(0)

# send get request and receive response from server
with s:
    s.sendall(request.encode('utf-8'))
    response = b''
    while True:
        part = s.recv(4096)
        response += part
        if len(part) < 4096:
            break

# parse data to json
try:
    str_response = response.decode('utf-8')
    split_response = re.split('{', str_response, 1)
    json_data = json.loads('{' + split_response[1])

except:
    print('error getting data from API response')
    sys.exit(0)

# wrong api request
if json_data['cod'] != 200:
    print(json_data['message'])
    sys.exit(0)

# print out weather
print()
print(json_data['name'])
print(json_data['weather'][0]['description'])
print('temperature: {0}{1}'.format(json_data['main']['temp'], '°C'))
print('humidity: {0}{1}'.format(json_data['main']['humidity'], '%'))
print('pressure: {0}{1}'.format(json_data['main']['pressure'], 'hPa'))
print('wind speed: {0}{1}'.format(json_data['wind']['speed'], 'm/s'))
try:
    print('wind direction: {0}{1}'.format(json_data['wind']['deg'], '°'))
except KeyError as msg:
    print('wind direction: n/a')

print()
