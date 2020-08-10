# DXX-Rebirth Tracker
This is a simple tracker for DXX-Rebirth written in .NET Core and PHP.

The tracker consists of 2 primary parts:

1. The backend, which is written in C# and contained in the `RebirthTracker` directory

2. The frontend. This is contained in the `web` directory and is made up of the following:
  - `index.php` - The primary webpage
  - `games.php` - A script to retrieve the list of games and display them
  - `backend.php` - A script to check if the backend is actually running

To use this tracker, you can set the ports in `Globals.cs` (default is 9999 for the primary port and 9998 for the external ack port), then build and run using Visual Studio or the `dotnet` commandline tool.

The tracker expects to receive packets in the following format: `<OPCODE><PARAMETERS>`, where `<OPCODE>` is an integer and `<PARAMETERS>` is a string of game information.

The game information string is in the format `a=IP/PORT,b=HEADER,c=ID,z=BLOB`. The key `a` is reserved for the IP/Port information and is in the format `a=127.0.0.1/42424`. The key `b` is reserved for the header and is a string in any format set by the client. The key `c` is reserved for the ID of the game set by the tracker and is of type short. The key `z` is reserved for the game info blob and must be the last key of the string.

The opcodes are as follows:

  `21`: Register a game with the tracker. The format is `21b=HEADER,z=BLOB`. Whenever the tracker recieves another info packet from the same IP address & port, it will update the currently hosted game.

  `22`: Remove a game from the tracker. (The host only needs to send the opcode).

  `23`: Retrieve a list of games. The format is`23HEADER` (You do not need to pass the `b=` because it is assumed). This will send each game in its own packet formatted as a string (as shown below).
  
  `24`: Game list sent to the client. The format is `24a=IP/PORT,c=ID,z=BLOB`. (The `b=HEADER` is not passed, since only games which match the header received will be sent, and the header would always be the same.)

  `25`: ACK packet. The format is simply the opcode followed by a 0 for internal ACK or a 1 for external ACK.

  `26`: Request/perform a hole-punch. The format received by the tracker is `26ID`, and the format sent by the tracker is `26IP/Port`. When a client requests a hole-punch, it will send this packet to the tracker with the ID of the game it wants to connect to. The host will receive this packet from the tracker with the IP/PORT of a client it should send packets to.
  
  NOTE: In this README "client" refers to a Rebirth executable attempting to join a game, "host" refers to a Rebirth executable hosting a game, and "tracker" refers to the server running the tracker code.
