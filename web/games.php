<?php
// Get a list of games and display them

$games = new SQLite3('../games.sqlite') or die('Unable to open database');
$games->busyTimeout(30000);

$queryString = "SELECT * FROM Games";

if (isset($_GET['version'])) {
    switch($_GET['version']) {
        case "d1x":
            $queryString = "SELECT * FROM Games WHERE DescentVersion = 1";
            break;
        case "d2x":
            $queryString = "SELECT * FROM Games WHERE DescentVersion = 2";
            break;
    }
}

$result = $games->query($queryString);

while($game = $result->fetchArray(SQLITE3_ASSOC)) {
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
    $missionLink = "https://enspiar.com/dmdb/index.php?keywords=" . urlencode($mission) . "&searchBox=" . urlencode($mission);
?>

<tr>
    <td><?php echo $game['VersionString']; ?></td>
    <td><?php echo $game['Name']; ?></td>
    <td><a target="_blank" style="color: #767cc9" href="<?php echo $missionLink; ?>"><?php echo $mission; ?></a></td>
    <td><?php echo $game['NumConnected']; ?>/<?php echo $game['MaxPlayers']; ?></td>
    <td><?php echo $gameMode; ?></td>
    <td><?php echo $game['Status']; ?></td>
    <td><?php echo $game['HostString']; ?></td>
</tr>

<?php
} // while

$games->close();
unset($games);

?>
