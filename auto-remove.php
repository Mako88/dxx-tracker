<?php

$games = new SQLite3('games.sqlite') or die('Unable to open database');

while(1) {
    // Every 10 seconds delete any game that hasn't been updated in 30 seconds
    
    $result = $games->query("SELECT * FROM games");
    
    while($game = $result->fetchArray()) {
        if(time() - $game['Time'] > 30) {
            $query = $games->prepare("DELETE FROM games WHERE a = :val");
            $query->bindValue(':val', $game['a'], SQLITE3_TEXT);
            $query->execute();
        }
    }
    sleep(10);
}

?>
