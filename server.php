<?php
set_time_limit(0);

$date = date("[y/m/d G:i:s] ");

$children = array();

$gametime = array();

$games = new SQLite3('games.sqlite') or die('Unable to open database');
$games->busyTimeout(30000);

$query = "CREATE TABLE IF NOT EXISTS games (peer STRING, header STRING, id INTEGER PRIMARY KEY, blob BLOB, time INTEGER)";
$games->exec($query) or die('Could not create database');

echo $date . "Initialized database\n";

$games->close();
unset($games);

// Create the primary socket
$socket = stream_socket_server("udp://0.0.0.0:9999", $errno, $errstr, STREAM_SERVER_BIND);

// Create the ACK socket
$acksocket = stream_socket_server("udp://0.0.0.0:9998", $ackerrno, $ackerrstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket || !$acksocket) {
    die("$errstr ($errno), $ackerrstr ($ackerrno)");
}

echo $date . "Opened sockets\n";

file_put_contents("server.pid", getmypid());

echo $date . "Created PID file\n";

// Primary server loop
while(1) {
    $games = new SQLite3('games.sqlite');
    $games->busyTimeout(30000);

    echo $date . "Waiting for packet...\n";
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);

    $peer = convertPeer($peer);

    // Split opcode from packet
    $oparray = unpack("Copcode/a*game", $pkt);
    $pkt = $oparray['game'];
    
    if(is_numeric($oparray['opcode'])) {
        echo $date . "Recieved opcode " . $oparray['opcode'] . " from " . $peer . "\n";
    }
    else {
        echo $date . "Recieved non-numeric opcode from " . $peer . "\n";
    }
    

    switch($oparray['opcode']) {

        // Register a game
        case 21:
            // Convert the received string into an array, adding the update time.
            $current = array();
            $start = 0;
            while(preg_match("/ ([a-y]+) = ([^,]+) /x", substr($pkt, $start), $match, PREG_OFFSET_CAPTURE)) {
                $current[$match[1][0]] = $match[2][0];
                $start += strlen($match[2][0]) + $match[2][1];
            }
            preg_match("/ z= (.+) /xs", substr($pkt, $start), $match);

            // Ignore the packet if it doesn't have a blob
            if(empty($match[1])) {
                break;
            }

            $current['blob'] = $match[1];
            $current['time'] = time();
            $current['header'] = $current['b'];


            // Check number of games hosted by this IP. Limit to 20.
            $ip = '%' . substr($peer, 0, strpos($peer, "/")) . '%';
            $query = $games->prepare("SELECT * FROM games WHERE peer LIKE :val");
            $query->bindValue(':val', $ip, SQLITE3_TEXT);
            $numgames = $query->execute();            
            if($numgames->numColumns() > 20) {
                echo $date . "Could not add game. " . substr($peer, 0, strpos($peer, "/")) . " Already has the max number of games hosted.\n";
                break;
            }

            // Check if a game is already hosted by the peer
            $query = $games->prepare("SELECT * FROM games WHERE peer = :val");
            $query->bindValue(':val', $peer, SQLITE3_TEXT);
            $result = $query->execute();

            // If a game is already hosted, just change the information
            if($game = $result->fetchArray(SQLITE3_ASSOC)) {
                $game = array_merge($game, $current);

                $query = $games->prepare("UPDATE games SET header = :header, blob = :blob, time = :time WHERE peer = :peer");
                $query->bindValue(':peer', $peer, SQLITE3_TEXT);
                $query->bindValue(':header', $game['header'], SQLITE3_TEXT);
                $query->bindValue(':blob', $game['blob'], SQLITE3_BLOB);
                $query->bindValue(':time', $game['time'], SQLITE3_INTEGER);
                $query->execute();

                echo $date . "Updated GameID " . $game['id'] . "\n";

            }
            // If a game isn't already hosted, create it
            else {

                $ids = array();
                $game = $current;

                $result = $games->query("SELECT id FROM games");

                if($result->numColumns() > 32765) {
                    echo $date . "Could not add game. Max number reached.\n";
                    break;
                }
                
                // Remove any games that have been hosted longer than a second
                $gametime = array_filter($gametime, "gameTime");
                
                $ip = substr($peer, 0, strpos($peer, "/"));
                
                // Check if this IP has hosted a game in the last second
                if(array_key_exists($ip, $gametime)) {
                    echo $date . "Could not add game. " . $ip . " tried to host too quickly.\n";
                    break;
                }
                else {
                    // Add this game's start time
                    $gametime[$ip] = time();
                }

                while($id = $result->fetchArray(SQLITE3_ASSOC)) {
                    $ids[] = $id['id'];
                }

                do {
                    $newid = rand(1, 32766);
                } while(in_array($newid, $ids));

                $query = $games->prepare("INSERT INTO games VALUES(:peer, :header, :id, :blob, :time)");
                $query->bindValue(':peer', $peer, SQLITE3_TEXT);
                $query->bindValue(':header', $game['header'], SQLITE3_TEXT);
                $query->bindValue(':id', $newid, SQLITE3_INTEGER);
                $query->bindValue(':blob', $game['blob'], SQLITE3_BLOB);
                $query->bindValue(':time', $game['time'], SQLITE3_INTEGER);
                $query->execute();

                echo $date . "Listed GameID " . $newid . "\n";

                // Send internal ACKs
                sendACK(0, $peer);
                echo $date . "Sent Internal ACKs to " . $peer . "\n";

                // Send external ACKs
                sendACK(1, $peer);
                echo $date . "Sent External ACKs to " . $peer . "\n";
            }

        break;

        // Unregister a game
        case 22:

            $query = $games->prepare("DELETE FROM games WHERE peer = :val");
            $query->bindValue(':val', $peer, SQLITE3_TEXT);
            $query->execute();
            echo $date . "Removed game hosted by " . $peer . "\n";

        break;

        // List games
        case 23:
            
            // Delete any game that hasn't been updated in 30 seconds
            echo $date . "Auto-removing stale games.\n";
            $query = $games->prepare("DELETE FROM games WHERE :curtime - time > 30");
            $query->bindValue(':curtime', time(), SQLITE3_INTEGER);
            $query->execute();

            $opcode = pack("C*", 24);

            // Only send games with the same header
            $query = $games->prepare("SELECT * FROM games WHERE header = :val");
            $query->bindValue(':val', $pkt, SQLITE3_TEXT);
            $result = $query->execute();

            while($game = $result->fetchArray(SQLITE3_ASSOC)) {
                $packet = $opcode;
                $packet .= "a=" . $game['peer'] . ",c=" . pack("S", $game['id']) . ",z=" . $game['blob'];
                stream_socket_sendto($socket, $packet, 0, convertPeer($peer, true));
                echo $date . "Sending GameID " . $game['id'] . "\n";
            }

        break;

        // Perform hole-punch
        case 26:

            // Unpack the GameID
            $temp = array();
            $temp = unpack("Spkt", $pkt);
            $pkt = $temp['pkt'];
            
            if(!is_numeric($pkt)) {
                echo $date . "Recieved non-numeric GameID from " . $peer . "\n";
                break;
            }

            // Get the game the client wants
            $query = $games->prepare("SELECT * FROM games WHERE id = :val");
            $query->bindValue(':val', $pkt, SQLITE3_TEXT);
            $result = $query->execute();
            echo $date . "Finding GameID " . $pkt . "\n";

            // Tell the host to send some packets to the client
            if($game = $result->fetchArray(SQLITE3_ASSOC)) {
                $packet = pack("C*", 26);
                $packet .= $peer;
                stream_socket_sendto($socket, $packet, 0, convertPeer($game['peer'], true));
                echo $date . "Sending " . $peer . " to " . $game['peer'] . "\n";
            }
            else {
                $packet = pack("C*", 27);
                $packet .= pack("S", $pkt);
                stream_socket_sendto($socket, $packet, 0, convertPeer($peer), true);
                echo $date . "Informing " . $peer . " that GameID " . $pkt . " is invalid\n";
            }

        break;
    }
    $games->close();
    unset($games);

}


function sendACK($type, $peer) {
    global $socket, $acksocket;
    
    $packet = pack("C*", 25);
    $packet .= pack("C*", $type);
    
    $peer = convertPeer($peer, true);
    
    for($i = 0; $i < 5; $i++) {
        // External ACK
        if($type) {
            stream_socket_sendto($acksocket, $packet, 0, $peer);
        }
        // Internal ACK
        else {
            stream_socket_sendto($socket, $packet, 0, $peer);
        }
    }
}


// This function converts a socket string to either a storable or usable format
function convertPeer($socket, $usable = false) {
    // By default convert an IP string to storable/sendable format.
    if(!$usable) {
        $i = strrpos($socket, ":");
        $result = substr_replace($socket, "/", $i, 1);
    }
    // If usable is set, convert it to a usable format.
    else {
        $i = strrpos($socket, "/");
        // IPv4
        if(strpos($socket, ":") === false) {
            $result = substr_replace($socket, ":", $i, 1);
        }
        // IPv6
        else {
            $result = substr_replace($socket, "]:", $i, 1);
            $result = '[' . $result;
        }
    }
    
    return $result;
}

function gameTime($var) {
    return((time() - $var) < 1);
}

?>
