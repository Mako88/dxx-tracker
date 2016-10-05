<?php
    // TODO: Add file locks
    // TODO: Don't display error when no games are running
    // TODO: Styling
    // TODO: Do we need to display difficulty?
    $filegames = json_decode(file_get_contents('../games.json'), true);
?>
<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">

  <title>DXX-Rebirth Tracker</title>
  <meta name="description" content="A game tracker for DXX-Rebirth.">
  <meta name="author" content="A Future Pilot">

  <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>DXX-Rebirth Tracker</h1>
    
    
    <?php
        foreach($filegames as $index => $game) {
            $games[$index] = unpack("Cupid/Smajor/Sminor/Smicro/Iid/Ilevelnum/Cgamemode/Crefuse/Cdifficulty/Cstatus/Cnumconnected/Cmaxplayers/Cflag/a*strings", base64_decode($game['c']));
            
            $games[$index]['host'] = $game['a'];
            
            $strings = explode("\x00", $games[$index]['strings']);

            $games[$index]['gamename'] = $strings[0];
            $games[$index]['missiontitle'] = $strings[1];
            $games[$index]['missionname'] = $strings[2];

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
            
            echo "
            <div class='game'>
                <span>" . $games[$index]['major'] . "." . $games[$index]['minor'] . "." . $games[$index]['micro'] . "</span>
                <span>" . $games[$index]['gamename'] . "</span>
                <span>" . $games[$index]['missiontitle'] . "</span>
                <span>" . $games[$index]['numconnected'] . "/" . $games[$index]['maxplayers'] . "</span>
                <span>" . $games[$index]['gamemode'] . "</span>
                <span>" . $games[$index]['status'] . "</span>
                <span>" . $games[$index]['host'] . "</span>
            </div>
            ";
        }
    ?>
    
</body>
</html>