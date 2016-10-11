<?php
// Get a list of games and display them

$games = new SQLite3('../games.sqlite') or die('Unable to open database');
$games->busyTimeout(3000);

$result = $games->query("SELECT * FROM games");
$games->exec('COMMIT;');

while($packgame = $result->fetchArray(SQLITE3_ASSOC)) {
    $strings = array();
    
    // Unpack based on the order the client sends the info
    $game = unpack("Cupid/Smajor/Sminor/Smicro/Iid/Ilevelnum/Cgamemode/Crefuse/Cdifficulty/Cstatus/Cnumconnected/Cmaxplayers/Cflag/a*strings", $packgame['c']);
    
    // Get the host and the game version (D1X or D2X)
    $game['host'] = $packgame['a'];
    preg_match("/D[1-2]X/", $packgame['b'], $game['version']);
    
    // Split the strings and set them to keys (favor Mission Title over Mission Name)
    $strings = explode("\x00", $game['strings']);
    
    // Game, Title, and Name were all set and under max. Use Title.
    if(!empty($strings[2])) {
        $game['gamename'] = $strings[0];
        $game['mission'] = $strings[1];
    }
    // Either one wasn't set, or one is over max length
    else if(!empty($strings[1])) {
        // If Game is over max length, all 3 were set. Get Game and Title.
        if(strlen($strings[0]) >= 15) {
            $game['gamename'] = substr($strings[0], 0, 15);
            $game['mission'] = substr($strings[0], 15);
        }
        else {
            // Get Game since it's under max length
            $game['gamename'] = $strings[0];
            // If Title is over max length, just get Title
            if(strlen($strings[1]) >= 25) {
                $game['mission'] = substr($strings[1], 0, 25);
            }
            // Otherwise get whatever's there (could be Title or Name)
            else {
                $game['mission'] = $strings[1];
            }
        }
    }
    // Both Game and Title are over max. Get both.
    else {
        $game['gamename'] = substr($strings[0], 0, 15);
        $game['mission'] = substr($strings[0], 15, 25);
    }
    
    
    // Set the game mode to text
    switch($game['gamemode']) {
        case 0:
            $game['gamemode'] = "Anarchy";
        break;

        case 1:
            $game['gamemode'] = "Team Anarchy";
        break;

        case 2:
            $game['gamemode'] = "Robo-Anarchy";
        break;

        case 3:
            $game['gamemode'] = "Cooperative";
        break;

        case 4:
            $game['gamemode'] = "Capture the Flag";
        break;

        case 5:
            $game['gamemode'] = "Hoard";
        break;

        case 6:
            $game['gamemode'] = "Team Hoard";
        break;

        case 7:
            $game['gamemode'] = "Bounty";
        break; 
    }
    
    // Set the difficulty (this isn't currently used)
    /*switch($game['difficulty']) {
        case 0:
            $game['difficulty'] = "Trainee";
        break;

        case 1:
            $game['difficulty'] = "Rookie";
        break;

        case 2:
            $game['difficulty'] = "Hotshot";
        break;

        case 3:
            $game['difficulty'] = "Ace";
        break;

        case 4:
            $game['difficulty'] = "Insane";
        break;
    }*/
    
    // Set the status. This uses the same logic as Rebirth does internally.
    if($game['status'] == 4) {
        $game['status'] = "Forming";
    }
    else if($game['status'] == 1) {
        if($game['refuse'] == 1) {
            $game['status'] = "Restricted";
        }
        else if($game['flag'] == 5) {
            $game['status'] = "Closed";
        }
        else {
            $game['status'] = "Open";
        }
    }
    else {
        $game['status'] = "Between";
    }
    
    // Conditionally echo the game depending on if the current game is the same version we want.
    if( (isset($_GET['d1x']) && $game['version'][0] == "D1X") || (isset($_GET['d2x']) && $game['version'][0] == "D2X") ) {
        echo "
        <tr>
            <td>" . $game['version'][0] . " " . $game['major'] . "." . $game['minor'] . "." . $game['micro'] . "</td>
            <td>" . $game['gamename'] . "</td>
            <td>" . $game['mission'] . "</td>
            <td>" . $game['numconnected'] . "/" . $game['maxplayers'] . "</td>
            <td>" . $game['gamemode'] . "</td>
            <td>" . $game['status'] . "</td>
            <td>" . $game['host'] . "</td>
        </tr>
        ";
    }
    
}
?>