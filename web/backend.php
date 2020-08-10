<?php

// Get the PID of the server
$serverpid = file_get_contents("../server.pid");

$isWindows = stripos(PHP_OS, 'WIN') === 0;

// Check if it's running and echo the results
if (file_exists("/proc/$serverpid") || $isWindows && PIDExists($serverpid)) {
    echo "<span class=\"up\">UP</span>";
}
else {
    echo "<span class=\"down\">DOWN</span>";
}

function PIDExists($serverpid)
{
    exec('TASKLIST /NH /FO "CSV" /FI "PID eq ' . $serverpid . '"', $outputA );
    $outputB = explode( '","', $outputA[0] );
    return isset($outputB[1]);
}
?>
