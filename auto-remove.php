<?php

while(1) {
    echo "Checking games...\n";
    $games = json_decode(file_get_contents('games.json'), true);
    $unset = false;

    foreach($games as $index => $game) {
        foreach($game as $key => $value) {
            if($key == "Time") {
                if(time() - $value > 10) {
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
    sleep(5);
}

?>