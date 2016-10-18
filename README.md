# DXX-Rebirth Tracker
This is a simple tracker for DXX-Rebirth written in PHP.

The tracker consists of 2 primary parts:

1. The backend, which is contained in the `server.php` file

2. The frontend. This is contained in the `web` directory and is made up of the following:
  1. `index.php` - The primary webpage
  2. `games.php` - A script to retrieve the list of games and display them
  3. `backend.php` - A script to check if the backend is actually running

`client.php` is included as a test client when a Rebirth client is not available.

To use this tracker, you can set the port at the top of `server.php` (default is 9999), then run `php server.php` from the command line.

The tracker expects to receive packets in the following format: `<OPCODE><PARAMETERS>`, where `<OPCODE>` is an integer and `<PARAMETERS>` is a string of game information.

The game information string is in the format `<OPCODE>a=IP/PORT,b=HEADER,c="Info 1",d="Info 2"`, etc. The key `a` is reserved for the IP/Port information and is in the format `a=127.0.0.1:42424`. The key `b` is reserved for the header and is a string in any format set by the client. The key `z` is reserved for the game info blob and must be the last key of the string.

The opcodes are as follows:

  `21`: Register a game with the tracker. The format is `21b=HEADER,c="Info 1",d="Info 2"`, etc. The game is stored in the file games.json. Whenever the tracker recieves another info packet from the same IP address & port, it will update the currently hosted game (this is how score is updated, etc.). Note: subsequent info packets only need to contain the updated info, and not the complete game info. (So sending `21f=5` will update the f variable, while leaving everything else intact).

  `22`: Remove a game from the tracker. (The client only needs to send the opcode).

  `23`: Retrieve a list of games. The format is`23HEADER` (You do not need to pass the `b=` because it is assumed). This will send each game in its own packet formatted as a string (as shown below).
  
  `24`: Game list sent to the client. The format is `24a=IP:PORT,c="Info 1",d="Info2"`, etc. (The `b=HEADER` is not passed, since only games which match the header received will be sent, and the header would always be the same.)

  `25`: ACK packet. The format is simply the opcode followed by a 0 for internal ACK or a 1 for external ACK.

  `26`: Request/perform a hole-punch. The format is `26IP/PORT`. When a client requests a hole-punch, it will send this packet to the tracker with the IP/PORT of the host it wants to connect to. The host will receive this packet from the tracker with the IP/PORT of a client it should send packets to.
