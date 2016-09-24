# DXX-Rebirth Tracker
This is a simple tracker for DXX-Rebirth written in PHP.

The tracker consists of 2 parts:

1. server.php - The tracker itself
2. auto-remove.php - A script to automatically remove games if they are inactive for a set amount of time (the client crashed).

To use this tracker, you can set the port at the top of server.php (default is 9999), then run both server.php and auto-remove.php from the command line (using the "php" command).

The tracker has 3 functions:

1. Adding games to the tracker. The tracker waits to receive an info packet that is a string consisting of the game parameters. It expects them in the format "GameInfo1=info 1,GameInfo2=info 2", etc. It will then store the game in the file games.json. Whenever the tracker recieves another info packet from the same IP address & port, it will update the currently hosted game (this is how score is updated, etc.). Note: subsequent info packets only need to contain the updated info, and not the complete game info. (So sending "Score=5" will update the Score variable, while leaving everything else intact).

2. Removing games fromt he tracker. If the tracker recieves a packet with the string "end", it will remove any game currently hosted by the IP address & port it received the packet from.

3. Sending game information. If the tracker receives a packet with the string "call", it will send a list of all currently running games to the client. This will be formatted as a string, with options set with "=", options separated by ",", and games separated by "/". (For example, the string
```
"Socket=127.0.0.1:42424,Name=Bob's Game,Players=5/Socket=192.168.1.1:12345,Name=Test/Socket=55.55.55.55:500,Name=Test 2,Score=12"
```
would show there are 3 games as follows:

 1.
 ```
     IP: 127.0.0.1
     Port: 42424
     Name: Bob's Game
     Players: 5
 ```
 2.
 ```
     IP: 192.168.1.1
     Port: 12345
     Name: Test
 ```
 3.
 ```
     IP: 55.55.55.55
     Port: 500
     Name: Test 2
     Score: 12
 ```
     
Note: The "Socket" variable is automatically added by the tracker to the beginning of each game string. Any required variables must be decided by the client.
