<?php
// Send 5 ACK packets (1 a second) to the peer passed as a parameter
$socket = stream_socket_client("udp://" . $argv[1], $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);

if (!$socket) {
    die("$errstr ($errno)");
}

for($i = 0; $i < 5; $i++) {
    $packet = pack("C*", 24);
    fwrite($socket, $packet);
    
    sleep(1);
}
?>
