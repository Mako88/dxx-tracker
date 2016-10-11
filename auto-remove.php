<?php

$games = new SQLite3('games.sqlite') or die('Unable to open database');
$games->busyTimeout(30000);

while(1) {
    // Every 2 seconds delete any game that hasn't been updated in 30 seconds
    
    $result = $games->query("SELECT * FROM games");
    
    while($game = $result->fetchArray(SQLITE3_ASSOC)) {
        if(time() - $game['Time'] > 30) {
            $query = $games->prepare("DELETE FROM games WHERE a = :val");
            $query->bindValue(':val', $game['a'], SQLITE3_TEXT);
            $query->execute();
        }
    }
    sleep(2);
}

?>
