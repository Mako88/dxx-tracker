# DXX-Rebirth Tracker
This is a simple tracker for DXX-Rebirth written in PHP.

The tracker consists of 4 parts:

1. server.php - The tracker itself
2. auto-remove.php - A script to automatically remove games if they are inactive for a set amount of time (the client crashed).
3. port-test.php - A script that sends test packets to the client to make sure their port is open
4. index.php - The web front-end.

To use this tracker, you can set the port at the top of server.php (default is 9999), then run server.php from the command line (using the "php" command).

The tracker expects to receive packets in the following format: `<OPCODE><PARAMETERS>`, where `<OPCODE>` is an integer and `<PARAMETERS>` is a string of game information.

The game information string is in the format `b=HEADER,c="Info 1",d="Info 2"`, etc. The key `a` is reserved for the IP/Socket information and is in the format `a=127.0.0.1:9999`. The key 'b' is reserved for the header and is in the format `b=d1x-0.60.0.1`.

The opcodes are as follows:

21. Register a game with the tracker. The format is `21b=HEADER,c="Info 1",d="Info 2"`, etc. (The IP/Socket info (key `a`) doesn't need to be passed because the tracker detects it automatically). The game is stored in the file games.json. Whenever the tracker recieves another info packet from the same IP address & port, it will update the currently hosted game (this is how score is updated, etc.). Note: subsequent info packets only need to contain the updated info, and not the complete game info. (So sending `21f=5` will update the f variable, while leaving everything else intact).

22. Remove a game from the tracker. The format is simply the opcode `22`.

23. Retrieve a list of games. The format is`23HEADER` (You do not need to pass the `b=` because it is assumed). This will return a list of games formatted as a string, with options set with "=", options separated by ",", and games separated by "/". For example:
```
"a=127.0.0.1:42424,c=Bob's Game,d=5/a=192.168.1.1:12345,c=Test/a=55.55.55.55:500,c=Test 2,d=12"
```
would show there are 3 games as follows:

 1.
 ```
     IP: 127.0.0.1
     Port: 42424
     c: Bob's Game
     d: 5
 ```
 2.
 ```
     IP: 192.168.1.1
     Port: 12345
     c: Test
 ```
 3.
 ```
     IP: 55.55.55.55
     Port: 500
     c: Test 2
     d: 12
 ```
 
 Once a game has been registered, 5 packets will be sent to it simply consisting of the opcode `24`. The client has to decide what action to take based on whether or not they are received.
