<?php
// Get a list of games and display them

$games = new SQLite3('../games.sqlite') or die('Unable to open database');
$games->busyTimeout(30000);

$result = $games->query("SELECT * FROM Games");

while($game = $result->fetchArray(SQLITE3_ASSOC)) {
    preg_match("/D[1-2]X/", $game['Header'], $dxxVersion);
    
    if (($dxxVersion[0] == "D1X" && !isset($_GET['d1x'])) || ($dxxVersion[0] == "D2X" && !isset($_GET['d2x']))) {
        continue;
    }

    // Set the game mode to text
    switch ($game['GameMode']) {
        case 0:
            $gameMode = "Anarchy";
            break;
        case 1:
            $gameMode = "Team Anarchy";
            break;
        case 2:
            $gameMode = "Robo-Anarchy";
            break;
        case 3:
            $gameMode = "Cooperative";
            break;
        case 4:
            $gameMode = "Capture the Flag";
            break;
        case 5:
            $gameMode = "Hoard";
            break;
        case 6:
            $gameMode = "Team Hoard";
            break;
        case 7:
            $gameMode = "Bounty";
            break; 
    }
    
    // Set the difficulty (this isn't currently used)
    /*switch($game['Difficulty']) {
        case 0:
            $difficulty = "Trainee";
            break;
        case 1:
            $difficulty = "Rookie";
            break;
        case 2:
            $difficulty = "Hotshot";
            break;
        case 3:
            $difficulty = "Ace";
            break;
        case 4:
            $difficulty = "Insane";
            break;
    }*/

    $mission = trim($game['MissionTitle']) == "" ? $game['MissionName'] : $game['MissionTitle'];
    
    echo "
    <tr>
        <td>" . $dxxVersion[0] . " " . $game['VersionString'] . "</td>
        <td>" . $game['Name'] . "</td>
        <td><a target=\"_blank\" style=\"color: #767cc9\" href=\"https://enspiar.com/dmdb/index.php?keywords=" . urlencode($mission) . "&searchBox=" . urlencode($mission) . "\">" . $mission . "</a></td>
        <td>" . $game['NumConnected'] . "/" . $game['MaxPlayers'] . "</td>
        <td>" . $gameMode . "</td>
        <td>" . $game['Status'] . "</td>
        <td>" . $game['HostString'] . "</td>
    </tr>
    ";
    
}
$games->close();
unset($games);
?>
