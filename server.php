<?php
set_time_limit(0);

$date = date("[y/m/d G:i:s]")

$children = array();

$games = new SQLite3('games.sqlite') or die('Unable to open database');
$games->busyTimeout(30000);

$query = "CREATE TABLE IF NOT EXISTS games (peer STRING, header STRING, id INTEGER PRIMARY KEY, blob BLOB, time STRING)";
$games->exec($query) or die('Could not create database');

echo $date . " Initialized database\n";

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

echo $date . " Opened sockets\n";

// Start the auto-remove process as a child
if (!$removepid = pcntl_fork()) {
    autoRemove();
}
// Continue the primary process
else {
    
    echo $date . " Started auto-remove process\n";
    // Primary server loop
    while(1) {
        $games = new SQLite3('games.sqlite');
        $games->busyTimeout(30000);
        
        echo $date . " Waiting for packet...\n";
        $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
        
        $peer = convertPeer($peer);
        
        // Split opcode from packet
        $oparray = unpack("Copcode/a*game", $pkt);
        $pkt = $oparray['game'];
        
        echo $date . " Recieved opcode " . $oparray['opcode'] . " from " . $peer . "\n";
            
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
                
                
                // Check number of games hosted by this IP. Limit to 20.
                $ip = substr($peer, 0, strpos($peer, "/"));
                $query = $games->prepare("SELECT * FROM games WHERE peer LIKE '%:val%'");
                $query->bindValue(':val', $ip, SQLITE3_TEXT);
                $numgames = $query->execute();
                if($numgames->numColumns() > 20) {
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
                    
                    echo $date . " Updated GameID " . $game['id'] . "\n";
                    
                }
                // If a game isn't already hosted, create it
                else {
                    
                    $ids = array();
                    $game = $current;
                    
                    $result = $games->query("SELECT id FROM games");
                    
                    if($result->numColumns() > 32765) {
                        echo $date . " Could not add game. Max number reached.\n";
                        break;
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
                    
                    echo $date . " Listed GameID " . $newid . "\n";
                    
                    // Start the internal ACK process as a child
                    if(!$internalpid = pcntl_fork()) {
                        sendACK(0);
                        exit();
                    }
                    else {
                        $children[] = $internalpid;
                        echo $date . " Sent Internal ACKs to " . $peer . "\n";
                    }
                    
                    // Start the external ACK process as a child
                    if(!$externalpid = pcntl_fork()) {
                        sendACK(1);
                        exit();
                    }
                    else {
                        $children[] = $externalpid;
                        echo $date . " Sent External ACKs to " . $peer . "\n";
                    }
                }
                
            break;
            
            // Unregister a game
            case 22:
                
                $query = $games->prepare("DELETE FROM games WHERE peer = :val");
                $query->bindValue(':val', $peer, SQLITE3_TEXT);
                $query->execute();
                echo $date . " Removed game hosted by " . $peer . "\n";
                
            break;
            
            // List games
            case 23:
                
                $opcode = pack("C*", 24);
                
                // Only send games with the same header
                $query = $games->prepare("SELECT * FROM games WHERE header = :val");
                $query->bindValue(':val', $pkt, SQLITE3_TEXT);
                $result = $query->execute();
                
                while($game = $result->fetchArray(SQLITE3_ASSOC)) {
                    $packet = $opcode;
                    $packet .= "a=" . $game['peer'] . ",c=" . pack("S", $game['id']) . ",z=" . $game['blob'];
                    stream_socket_sendto($socket, $packet, 0, convertPeer($peer, true));
                    echo $date . " Sending GameID " . $game['id'] . "\n";
                }
                
            break;
                
            // Perform hole-punch
            case 26:
            
                // Unpack the GameID
                $temp = array();
                $temp = unpack("Spkt", $pkt);
                $pkt = $temp['pkt'];
                
                $opcode = pack("C*", 26);
                
                // Get the game the client wants
                $query = $games->prepare("SELECT * FROM games WHERE id = :val");
                $query->bindValue(':val', $pkt, SQLITE3_TEXT);
                $result = $query->execute();
                echo $date . " Finding GameID " . $pkt . "\n";
                
                // Tell the host to send some packets to the client
                if($game = $result->fetchArray(SQLITE3_ASSOC)) {
                    $packet = $opcode;
                    $packet .= $peer;
                    stream_socket_sendto($socket, $packet, 0, convertPeer($game['peer'], true));
                    echo $date . " Sending " . $peer . " to " . $game['peer'] . "\n";
                }
                
            break;
        }
        $games->close();
        unset($games);
        
        // Clean up finished child processes
        foreach($children as $index=>$child) {
            if(pcntl_waitpid($child,$status,WNOHANG)) {
                unset($children[$index]);
            }
        }
    }
}

// This function automatically removes games that weren't ended manually
function autoRemove() {
    while(1) {
        $games = new SQLite3('games.sqlite');
        $games->busyTimeout(30000);
        // Every 5 seconds delete any game that hasn't been updated in 30 seconds
        
        $result = $games->query("SELECT * FROM games");
        
        while($game = $result->fetchArray(SQLITE3_ASSOC)) {
            if(time() - $game['time'] > 30) {
                $query = $games->prepare("DELETE FROM games WHERE peer = :val");
                $query->bindValue(':val', $game['peer'], SQLITE3_TEXT);
                $query->execute();
            }
        }
        sleep(5);
        $games->close();
        unset($games);
    }
}


function sendACK($type) {
    global $peer, $socket;
    
    $packet = pack("C*", 25);
    $packet .= pack("C*", $type);
    
    $peer = convertPeer($peer, true);
    
    for($i = 0; $i < 5; $i++) {
        stream_socket_sendto($socket, $packet, 0, $peer);
        sleep(1);
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

?>
