<?php

$socket = stream_socket_server("udp://127.0.0.1:9999", $errno, $errstr, STREAM_SERVER_BIND);

if (!$socket) {
    die("$errstr ($errno)");
}

while(1) {
    echo "Waiting for packet...\n";
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
    $pkt = trim($pkt);
    
    switch($pkt) {
        case "call":
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            $result = "";
            
            foreach($games as $index => $game) {
                foreach($game as $key => $value) {
                    if($key != "Time") {
                        $result .= "$key=$value,";
                    }
                }
                $result = rtrim($result, ",");
                $result .= "/";
            }
            $result = rtrim($result, "/");
                
            stream_socket_sendto($socket, $result, 0, $peer);
            unlink("lock");
            break;
        
        case "end":
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
            $running = false;
            while(file_exists("lock")) { usleep(100000); }
            touch("lock");
            $games = json_decode(file_get_contents('games.json'), true);
            preg_match_all("/ ([^,]+) = ([^,]+) /x", $pkt, $p);
            $current = array("Socket"=>$peer) + array_combine($p[1], $p[2]) + array("Time"=>time());
            
            foreach($games as $index => $game) {
                if($game['Socket'] == $peer) {
                    $games[$index] = array_merge($game, $current);
                    $running = true;
                }
            }
            
            if($running == false) {
                $games[] = $current;
            }
            
            file_put_contents("games.json", json_encode($games));
            unlink("lock");
    }
    echo "Recieved " . $pkt . " from " . $peer . "\n";
    
}

?>