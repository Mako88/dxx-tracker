<?php

// Create the socket
$socket = stream_socket_server("udp://0.0.0.0:9999", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

// Start the auto-remove process
shell_exec('php ' . __DIR__ . '/auto-remove.php > /dev/null 2>/dev/null &');
// Windows
//pclose(popen('start /B cmd /C php ' . __DIR__ . '/auto-remove.php >NUL 2>NUL', 'r'));

// Primary server loop
while(1) {
    echo "Waiting for packet...\n";
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
    $iparray = explode(":", $peer);
    
    // Split opcode from packet
    $oparray = unpack("Copcode/a*game", $pkt);
    $pkt = $oparray['game'];
        
    switch($oparray['opcode']) {
            
        // Register a game
        case 21:
            
            $running = false;
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            
            $games = json_decode(file_get_contents('games.json'), true);
            
            // Convert the received string into an array, adding the update time.
            preg_match_all("/ ([^,]+) = ([^,]+) /x", $pkt, $p);
            $current = array_combine($p[1], $p[2]) + array("Time"=>time());
            
            // $iparray[0] is the IP address, however $iparray[1] is the port used
            // to contact the tracker, NOT the port the game is hosted on. $host must
            // be set using the port sent in 'a'.
            $host = $iparray[0] . ':' . $current['a'];
            $current['a'] = $host;
            
            /*
            NOTE: The 'c' key (the primary game info) is always stored encoded in base64
            because json doesn't officially support binary strings, and storing them unencoded
            can lead to unexpected results. So every time 'c' is loaded, it must be decoded
            before sending it to a client or changing its info with new data.
            */
            
            // If a game is already hosted by the peer, just change the information
            foreach($games as $index => $game) {
                if($game['a'] == $host) {
                    $game['c'] = base64_decode($game['c']);
                    $games[$index] = array_merge($game, $current);
                    $games[$index]['c'] = base64_encode($games[$index]['c']);
                    $running = true;
                }
            }
            
            // If a game isn't already hosted, list it.
            if($running == false) {
                $current['c'] = base64_encode($current['c']);
                $games[] = $current;
                
                // Start the port-test process
                shell_exec('php ' . __DIR__ . '/port-test.php ' . $host . ' > /dev/null 2>/dev/null &');
                // Windows
                //pclose(popen('start /B cmd /C php ' . __DIR__ . '/port-test.php ' . $host . ' >NUL 2>NUL', 'r'));
            }
            
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
            break;
        
        // Unregister a game
        case 22:
            
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            $host = $iparray[0] . ':' . $pkt;
            
            // Only unregister a game with the same port. This would allow the same IP address
            // to host multiple games, though the client doesn't currently support this.
            foreach($games as $index => $game) {
                if($game['a'] == $host) {
                    unset($games[$index]);
                }
            }
            $games = array_values($games);
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
            break;
        
        // List games
        case 23:
            
            $opcode = pack("C*", 25);
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);

            foreach($games as $index => $game) {
                $result = $opcode;
                // Only send games with the same header
                if($game['b'] == $pkt) {
                    $game['c'] = base64_decode($game['c']);
                    foreach($game as $key => $value) {
                        // Don't send the time to the peer (they don't need it).
                        if($key != "Time" && $key != "b") {
                            $result .= "$key=$value,";
                        }
                    }
                    $result = rtrim($result, ",");

                    stream_socket_sendto($socket, $result, 0, $peer);
                }
            }
            
            unlink("lock");
    }
    echo "Recieved opcode " . $oparray['opcode'] . " from " . $peer . "\n";
}

?>
