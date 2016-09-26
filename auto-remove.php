<?php

while(1) {
    while(file_exists("lock")) { usleep(100000); }
    touch("lock");
    $games = json_decode(file_get_contents('games.json'), true);
    $unset = false;
    
    // Check the current games. If any haven't been updated in 30 seconds, remove them.
    foreach($games as $index => $game) {
        foreach($game as $key => $value) {
            if($key == "Time") {
                if(time() - $value > 30) {
                    echo "Removing game from " . $game["Socket"] . "\n";
                    unset($games[$index]);
                    $unset = true;
                }
            }
        }
    }
    
    if($unset == true) {
        file_put_contents("games.json", json_encode($games));
    }
    unlink("lock");
    sleep(10);
}

?>
