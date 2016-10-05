<?php
$found = false;
exec("ps aux", $output, $result);
// Windows
//exec("wmic process", $output, $result);
foreach ($output AS $line) if(strpos($line, "server.php")) $found = true;

if ($found == true) {
    echo "<span class=\"up\">UP</span>";
}
else {
    echo "<span class=\"down\">DOWN</span>";
}
?>