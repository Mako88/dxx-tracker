# DXX-Rebirth Tracker
This is a simple tracker for DXX-Rebirth written in PHP.

The tracker consists of 2 primary parts:

1. The backend. This is made up of the following:
  1. server.php - The tracker itself
  2. auto-remove.php - A script to automatically remove games if they are inactive for a set amount of time (the client crashed).
  3. port-test.php - A script that sends test packets to the client to make sure their port is open
2. The frontend. This is contained in the `web` directory and is made up of the following:
  1. index.php - The primary webpage
  2. games.php - A script to retrieve the list of games and display them
  3. backend.php - A script to check if the backend is actually running.

client.php is included as a test client when a Rebirth client is not available.

To use this tracker, you can set the port at the top of server.php (default is 9999), then run server.php from the command line (using the "php" command).

The tracker expects to receive packets in the following format: `<OPCODE><PARAMETERS>`, where `<OPCODE>` is an integer and `<PARAMETERS>` is a string of game information.

The game information string is in the format `<OPCODE>a=IP:PORT,b=HEADER,c="Info 1",d="Info 2"`, etc. The key `a` is reserved for the IP/Port information. The tracker expects to receive just the port (`a=42424`), but will send both the port and IP (`a=127.0.0.1:42424`). The key `b` is reserved for the header and is in the format `b=d1x-0.60.0.1`.

The opcodes are as follows:

  `21`: Register a game with the tracker. The format is `21a=PORT,b=HEADER,c="Info 1",d="Info 2"`, etc. The game is stored in the file games.json. Whenever the tracker recieves another info packet from the same IP address & port, it will update the currently hosted game (this is how score is updated, etc.). Note: subsequent info packets only need to contain the updated info, and not the complete game info. (So sending `21f=5` will update the f variable, while leaving everything else intact).

  `22`: Remove a game from the tracker. The format is `22PORT` (You do not need to pass the `a=` because it is assumed).

  `23`: Retrieve a list of games. The format is`23HEADER` (You do not need to pass the `b=` because it is assumed). This will send each game in its own packet formatted as a string (as shown below).
  
  `24`: ACK packet. Once a game has been registered, 5 packets will be sent to it simply consisting of the opcode `24`. The client has to decide what action to take based on whether or not they are received.
  
  `25`: This is the opcode for the list of games sent back to the client. The format is `25a=IP:PORT,c="Info 1",d="Info2"`, etc. (The `b=HEADER` is not passed, since only games which match the header received will be sent, and the header would always be the same.)

