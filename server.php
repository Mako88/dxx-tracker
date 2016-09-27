<?php

// Create the socket
$socket = stream_socket_server("udp://0.0.0.0:9999", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

// Start the auto-remove process
echo('php ' . __DIR__ . '/auto-remove.php > /dev/null 2>/dev/null &');

// Primary server loop
while(1) {
    echo "Waiting for packet...\n";
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
    $pkt = trim($pkt);
    
    $oparray = unpack("Copcode/a*game", $pkt);
    $pkt = $oparray['game'];
    
    // Sanitize packet
    $pkt = filter_var($pkt, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
    $pkt = filter_var($pkt, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
    
    switch($oparray['opcode']) {
        // Register a game
        case 21:
            
            $running = false;
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            
            // Convert the received string into an array, adding the socket info and the update time.
            preg_match_all("/ ([^,]+) = ([^,]+) /x", $pkt, $p);
            $current = array("a"=>$peer) + array_combine($p[1], $p[2]) + array("Time"=>time());
            
            // If a game is already hosted by the peer, just change the information
            foreach($games as $index => $game) {
                if($game['a'] == $peer) {
                    $games[$index] = array_merge($game, $current);
                    $running = true;
                }
            }
            
            // If a game isn't already hosted, list it.
            if($running == false) {
                $games[] = $current;
                // Start the port-test process
                shell_exec('php ' . __DIR__ . '/port-test.php' . $peer . '> /dev/null 2>/dev/null &');
            }
            
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
            break;
        
        // Unregister a game
        case 22:
            
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            foreach($games as $index => $game) {
                if($game['a'] == $peer) {
                    unset($games[$index]);
                }
            }
            $games = array_values($games);
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
            break;
        
        // List games
        case 23:
            
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            $result = "";
            
            // Iterate through the games and add them to a string
            foreach($games as $index => $game) {
                // Only send games with the same header
                if($game['b'] == $pkt) {
                    foreach($game as $key => $value) {
                        // Don't send the time to the peer (they don't need it).
                        if($key != "Time" && $key != "b") {
                            $result .= "$key=$value,";
                        }
                    }
                    $result = rtrim($result, ",");
                    $result .= "/";
                }
            }
            $result = rtrim($result, "/");
            
            // Send the string to the peer
            stream_socket_sendto($socket, $result, 0, $peer);
            unlink("lock");
    }
}

?>