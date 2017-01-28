<?php

// Get the PID of the server
$serverpid = file_get_contents("../server.pid");

// Check if it's running and echo the results
if (file_exists("/proc/$serverpid")) {
    echo "<span class=\"up\">UP</span>";
}
else {
    echo "<span class=\"down\">DOWN</span>";
}
?>
