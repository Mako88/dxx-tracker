<?php

while(file_exists("../lock")) { usleep(100000); }
touch("../lock");
$filegames = json_decode(file_get_contents('../games.json'), true);
unlink("../lock");
$games = array();

if($filegames == false) {
    $filegames = array();
}

foreach($filegames as $index => $game) {
    $games[$index] = unpack("Cupid/Smajor/Sminor/Smicro/Iid/Ilevelnum/Cgamemode/Crefuse/Cdifficulty/Cstatus/Cnumconnected/Cmaxplayers/Cflag/a*strings", base64_decode($game['c']));

    $games[$index]['host'] = $game['a'];
    preg_match("/D[1-2]X/", $game['b'], $games[$index]['version']);

    $strings = explode("\x00", $games[$index]['strings']);

    $games[$index]['gamename'] = $strings[0];
    if(isset($strings[1])) {
        $games[$index]['mission'] = $strings[1];
    }
    else if(isset($strings[2])) {
        $games[$index]['mission'] = $strings[2];
    }

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