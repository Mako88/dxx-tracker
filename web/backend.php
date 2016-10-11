<?php
// Check if the backend is running

$found = false;

// Get a list of the current processes
exec("ps aux | grep -v tmux", $output, $result);
// Windows
//exec("wmic process", $output, $result);

// See if one of them contains "server.php"
foreach ($output AS $line) if(strpos($line, "server.php")) $found = true;

// Echo the results
if ($found == true) {
    echo "<span class=\"up\">UP</span>";
}
else {
    echo "<span class=\"down\">DOWN</span>";
}
?>
