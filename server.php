<?php
set_time_limit(0);
// Create the socket
$socket = stream_socket_server("udp://0.0.0.0:9999", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

$games = new SQLite3('games.sqlite') or die('Unable to open database');
$games->busyTimeout(3000);
$query = "CREATE TABLE IF NOT EXISTS games (a STRING PRIMARY KEY, b STRING, c BLOB, Time STRING)";
$games->exec($query) or die('Could not create database');


// Start the auto-remove process
shell_exec('php ' . __DIR__ . '/auto-remove.php > /dev/null 2>/dev/null &');
// Windows
//pclose(popen('start /B cmd /C php ' . __DIR__ . '/auto-remove.php >NUL 2>NUL', 'r'));

// Primary server loop
while(1) {
    echo "Waiting for packet...\n";
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
    
    // Split opcode from packet
    $oparray = unpack("Copcode/a*game", $pkt);
    $pkt = $oparray['game'];
    
    echo "Recieved opcode " . $oparray['opcode'] . " from " . $peer . "\n";
        
    switch($oparray['opcode']) {
            
        // Register a game
        case 21:
            // Convert the received string into an array, adding the update time.
            preg_match_all("/ ([^,]+) = ([^,]+) /x", $pkt, $p);
            $current = array_combine($p[1], $p[2]) + array("Time"=>time());
            
            
            // Check if a game is already hosted by the peer
            $query = $games->prepare("SELECT * FROM games WHERE a = :val");
            $query->bindValue(':val', $peer, SQLITE3_TEXT);
            $result = $query->execute();
            
            // If a game is already hosted, just change the information
            if($game = $result->fetchArray(SQLITE3_ASSOC)) {
                $game = array_merge($game, $current);
                
                $query = $games->prepare("UPDATE games SET b = :b, c = :c, Time = :Time WHERE a = :a");
                $query->bindValue(':a', $peer, SQLITE3_TEXT);
                $query->bindValue(':b', $game['b'], SQLITE3_TEXT);
                $query->bindValue(':c', $game['c'], SQLITE3_BLOB);
                $query->bindValue(':Time', $game['Time'], SQLITE3_INTEGER);
                $query->execute();
            }
            // If a game isn't already hosted, create it
            else {
                $game = $current;
                                
                $query = $games->prepare("INSERT INTO games VALUES(:a, :b, :c, :Time)");
                $query->bindValue(':a', $peer, SQLITE3_TEXT);
                $query->bindValue(':b', $game['b'], SQLITE3_TEXT);
                $query->bindValue(':c', $game['c'], SQLITE3_BLOB);
                $query->bindValue(':Time', $game['Time'], SQLITE3_INTEGER);
                $query->execute();
                
                // Start the port-test process
                shell_exec('php ' . __DIR__ . '/port-test.php ' . $peer . ' > /dev/null 2>/dev/null &');
                // Windows
                //pclose(popen('start /B cmd /C php ' . __DIR__ . '/port-test.php ' . $peer . ' >NUL 2>NUL', 'r'));
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
                $packet .= "a=" . $game['a'] . ",c=" . $game['c'];
                stream_socket_sendto($socket, $packet, 0, $peer);
            }
    }
}

?>
