<?php
// Get a list of games and display them

while(file_exists("../lock")) { usleep(100000); }
touch("../lock");
$filegames = json_decode(file_get_contents('../games.json'), true);
unlink("../lock");
$games = array();
$strings = array();

// If there aren't any games, make sure the foreach below doesn't fail.
if($filegames == false) {
    $filegames = array();
}

foreach($filegames as $index => $game) {
    // Unpack based on the order the client sends the info
    $games[$index] = unpack("Cupid/Smajor/Sminor/Smicro/Iid/Ilevelnum/Cgamemode/Crefuse/Cdifficulty/Cstatus/Cnumconnected/Cmaxplayers/Cflag/a*strings", base64_decode($game['c']));
    
    // Get the host and the game version (D1X or D2X)
    $games[$index]['host'] = $game['a'];
    preg_match("/D[1-2]X/", $game['b'], $games[$index]['version']);
    
    // Split the strings and set them to keys (favor Mission Title over Mission Name)
    $strings = explode("\x00", $games[$index]['strings']);
    
    // Game, Title, and Name were all set and under max. Use Title.
    if(!empty($strings[2])) {
        $games[$index]['gamename'] = $strings[0];
        $games[$index]['mission'] = $strings[1];
    }
    // Either one wasn't set, or one is over max length
    else if(!empty($strings[1])) {
        // If Game is over max length, all 3 were set. Get Game and Title.
        if(strlen($strings[0]) >= 15) {
            $games[$index]['gamename'] = substr($strings[0], 0, 15);
            $games[$index]['mission'] = substr($strings[0], 15);
        }
        else {
            // Get Game since it's under max length
            $games[$index]['gamename'] = $strings[0];
            // If Title is over max length, just get Title
            if(strlen($strings[1]) >= 25) {
                $games[$index]['mission'] = substr($strings[1], 0, 25);
            }
            // Otherwise get whatever's there (could be Title or Name)
            else {
                $games[$index]['mission'] = $strings[1];
            }
        }
    }
    // Both Game and Title are over max. Get both.
    else {
        $games[$index]['gamename'] = substr($strings[0], 0, 15);
        $games[$index]['mission'] = substr($strings[0], 15, 25);
    }
    
    
    // Set the game mode to text
    switch($games[$index]['gamemode']) {
        case 0:
            $games[$index]['gamemode'] = "Anarchy";
        break;

        case 1:
            $games[$index]['gamemode'] = "Team Anarchy";
        break;

        case 2:
            $games[$index]['gamemode'] = "Robo-Anarchy";
        break;

        case 3:
            $games[$index]['gamemode'] = "Cooperative";
        break;

        case 4:
            $games[$index]['gamemode'] = "Capture the Flag";
        break;

        case 5:
            $games[$index]['gamemode'] = "Hoard";
        break;

        case 6:
            $games[$index]['gamemode'] = "Team Hoard";
        break;

        case 7:
            $games[$index]['gamemode'] = "Bounty";
        break; 
    }
    
    // Set the difficulty (this isn't currently used)
    switch($games[$index]['difficulty']) {
        case 0:
            $games[$index]['difficulty'] = "Trainee";
        break;

        case 1:
            $games[$index]['difficulty'] = "Rookie";
        break;

        case 2:
            $games[$index]['difficulty'] = "Hotshot";
        break;

        case 3:
            $games[$index]['difficulty'] = "Ace";
        break;

        case 4:
            $games[$index]['difficulty'] = "Insane";
        break;
    }
    
    // Set the status. This uses the same logic as Rebirth does internally.
    if($games[$index]['status'] == 4) {
        $games[$index]['status'] = "Forming";
    }
    else if($games[$index]['status'] == 1) {
        if($games[$index]['refuse'] == 1) {
            $games[$index]['status'] = "Restricted";
        }
        else if($games[$index]['flag'] == 5) {
            $games[$index]['status'] = "Closed";
        }
        else {
            $games[$index]['status'] = "Open";
        }
    }
    else {
        $games[$index]['status'] = "Between";
    }
    
    // Conditionally echo the game depending on if the current game is the same version we want.
    if( (isset($_GET['d1x']) && $games[$index]['version'][0] == "D1X") || (isset($_GET['d2x']) && $games[$index]['version'][0] == "D2X") ) {
        echo "
        <tr>
            <td>" . $games[$index]['version'][0] . " " . $games[$index]['major'] . "." . $games[$index]['minor'] . "." . $games[$index]['micro'] . "</td>
            <td>" . $games[$index]['gamename'] . "</td>
            <td>" . $games[$index]['mission'] . "</td>
            <td>" . $games[$index]['numconnected'] . "/" . $games[$index]['maxplayers'] . "</td>
            <td>" . $games[$index]['gamemode'] . "</td>
            <td>" . $games[$index]['status'] . "</td>
            <td>" . $games[$index]['host'] . "</td>
        </tr>
        ";
    }
}
?>