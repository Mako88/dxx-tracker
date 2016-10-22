<?php
set_time_limit(0);

$children = array();

$games = new SQLite3('games.sqlite') or die('Unable to open database');
$games->busyTimeout(30000);

$query = "CREATE TABLE IF NOT EXISTS games (a STRING, b STRING, c STRING PRIMARY KEY, z BLOB, Time STRING)";
$games->exec($query) or die('Could not create database');

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

// Start the auto-remove process as a child
if (!$removepid = pcntl_fork()) {
    autoRemove();
}
// Continue the primary process
else {
    // Primary server loop
    while(1) {
        $games = new SQLite3('games.sqlite') or die('Unable to open database');
        $games->busyTimeout(30000);
        
        echo "Waiting for packet...\n";
        $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
        
        $peer = convertPeer($peer);
        
        // Split opcode from packet
        $oparray = unpack("Copcode/a*game", $pkt);
        $pkt = $oparray['game'];
        
        echo "Recieved opcode " . $oparray['opcode'] . " from " . $peer . "\n";
            
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
                $current['z'] = $match[1];
                $current['Time'] = time();
                
                
                // Check if a game is already hosted by the peer
                $query = $games->prepare("SELECT * FROM games WHERE a = :val");
                $query->bindValue(':val', $peer, SQLITE3_TEXT);
                $result = $query->execute();
                
                // If a game is already hosted, just change the information
                if($game = $result->fetchArray(SQLITE3_ASSOC)) {
                    $game = array_merge($game, $current);
                    
                    $query = $games->prepare("UPDATE games SET b = :b, z = :z, Time = :Time WHERE a = :a");
                    $query->bindValue(':a', $peer, SQLITE3_TEXT);
                    $query->bindValue(':b', $game['b'], SQLITE3_TEXT);
                    $query->bindValue(':z', $game['z'], SQLITE3_BLOB);
                    $query->bindValue(':Time', $game['Time'], SQLITE3_INTEGER);
                    $query->execute();
                }
                // If a game isn't already hosted, create it
                else {
                    $game = $current;
                    
                    $result = $games->query("SELECT c FROM games");
                    
                    while($id = $result->fetchArray(SQLITE3_ASSOC)) {
                        $ids[] = $id['c'];
                    }
                    
                    do {
                        $c = rand(1, 32766);
                    } while(in_array($c, $ids));

                    $query = $games->prepare("INSERT INTO games VALUES(:a, :b, :c, :z, :Time)");
                    $query->bindValue(':a', $peer, SQLITE3_TEXT);
                    $query->bindValue(':b', $game['b'], SQLITE3_TEXT);
                    $query->bindValue(':c', $game['c'], SQLITE3_INTEGER);
                    $query->bindValue(':z', $game['z'], SQLITE3_BLOB);
                    $query->bindValue(':Time', $game['Time'], SQLITE3_INTEGER);
                    $query->execute();
                    
                    // Start the internal ACK process as a child
                    if(!$internalpid = pcntl_fork()) {
                        internalACK();
                        exit();
                    }
                    else {
                        $children[] = $internalpid;
                    }
                    
                    // Start the external ACK process as a child
                    if(!$externalpid = pcntl_fork()) {
                        externalACK();
                        exit();
                    }
                    else {
                        $children[] = $externalpid;
                    }
                }
                
            break;
            
            // Unregister a game
            case 22:
                
                $query = $games->prepare("DELETE FROM games WHERE a = :val");
                $query->bindValue(':val', $peer, SQLITE3_TEXT);
                $query->execute();
                
            break;
            
            // List games
            case 23:
                
                $opcode = pack("C*", 24);
                
                // Only send games with the same header
                $query = $games->prepare("SELECT * FROM games WHERE b = :val");
                $query->bindValue(':val', $pkt, SQLITE3_TEXT);
                $result = $query->execute();
                
                while($game = $result->fetchArray(SQLITE3_ASSOC)) {
                    $packet = $opcode;
                    $packet .= "a=" . $game['a'] . ",c=" . game['c'] . ",z=" . $game['z'];
                    stream_socket_sendto($socket, $packet, 0, convertPeer($peer, true));
                }
                
            // Perform hole-punch
            case 26:
                
                $opcode = pack("C*", 26);
                
                // Get the game the client wants
                $query = $games->prepare("SELECT * FROM games WHERE c = :val");
                $query->bindValue(':val', $pkt, SQLITE3_TEXT);
                $result = $query->execute();
                
                if($game = $result->fetchArray(SQLITE3_ASSOC)) {
                    $packet = $opcode;
                    $packet .= $peer;
                    stream_socket_sendto($socket, $packet, 0, convertPeer($game['a'], true));
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
        $games = new SQLite3('games.sqlite') or die('Unable to open database');
        $games->busyTimeout(30000);
        // Every 5 seconds delete any game that hasn't been updated in 30 seconds
        
        $result = $games->query("SELECT * FROM games");
        
        while($game = $result->fetchArray(SQLITE3_ASSOC)) {
            if(time() - $game['Time'] > 30) {
                $query = $games->prepare("DELETE FROM games WHERE a = :val");
                $query->bindValue(':val', $game['a'], SQLITE3_TEXT);
                $query->execute();
            }
        }
        sleep(5);
        $games->close();
        unset($games);
    }
}

// This function sends internal ACK packets
function internalACK() {
    
    global $peer, $socket;
    
    $packet = pack("C*", 25);
    $packet .= pack("C*", 0);
    for($i = 0; $i < 5; $i++) {
        stream_socket_sendto($socket, $packet, 0, convertPeer($peer, true));
        sleep(1);
    }
}

// This function sends external ACK packets
function externalACK() {
    
    global $peer, $acksocket;
    
    $packet = pack("C*", 25);
    $packet .= pack("C*", 1);
    for($i = 0; $i < 5; $i++) {
        stream_socket_sendto($acksocket, $packet, 0, convertPeer($peer, true));
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
        // IPv6
        if(strpos($socket, ":") === false) {
            $i = strrpos($socket, "/");
            $result = substr_replace($socket, "]:", $i, 1);
            $result = '[' . $result;
        }
        // IPv4
        else {
            $i = strrpos($socket, "/");
            $result = substr_replace($socket, ":", $i, 1);
        }
    }
    
    return $result;
}

?>
