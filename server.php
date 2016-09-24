<?php

// Create the socket
$socket = stream_socket_server("udp://0.0.0.0:9999", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

// Primary server loop
while(1) {
    echo "Waiting for packet...\n";
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
    $pkt = trim($pkt);
    
    switch($pkt) {
        case "call":
            // Create a lock file for games.json
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            $result = "";
            
            // Iterate through the games and add them to a string
            foreach($games as $index => $game) {
                foreach($game as $key => $value) {
                    // Don't send the time to the peer (they don't need it).
                    if($key != "Time") {
                        $result .= "$key=$value,";
                    }
                }
                $result = rtrim($result, ",");
                $result .= "/";
            }
            $result = rtrim($result, "/");
            
            // Send the string to the peer
            stream_socket_sendto($socket, $result, 0, $peer);
            unlink("lock");
            break;
        
        case "end":
            // Iterate through the games and remove the game hosted by the peer
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            foreach($games as $index => $game) {
                if($game['Socket'] == $peer) {
                    unset($games[$index]);
                }
            }
            $games = array_values($games);
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
            break;
            
        default:
            // Add the game to the list
            $running = false;
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            
            // Convert the received string into an array, adding the socket info and the update time.
            preg_match_all("/ ([^,]+) = ([^,]+) /x", $pkt, $p);
            $current = array("Socket"=>$peer) + array_combine($p[1], $p[2]) + array("Time"=>time());
            
            // If a game is already hosted by the peer, just change the information
            foreach($games as $index => $game) {
                if($game['Socket'] == $peer) {
                    $games[$index] = array_merge($game, $current);
                    $running = true;
                }
            }
            
            // If a game isn't already hosted, list it.
            if($running == false) {
                $games[] = $current;
            }
            
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
    }
    echo "Recieved " . $pkt . " from " . $peer . "\n";
    
}

?>