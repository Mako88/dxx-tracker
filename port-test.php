<?php
$peer = $argv[1];

// Create socket
$socket = stream_socket_server("udp://0.0.0.0:9999", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

// Send internal ACK packet
for($i = 0; $i < 5; $i++) {
    $packet = pack("C*", 25);
    $packet .= pack("C*", 0);
    stream_socket_sendto($socket, $packet, 0, $peer);

    sleep(1);
}

// Create socket
$socket = stream_socket_server("udp://0.0.0.0:9998", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

// Send external ACK packet
for($i = 0; $i < 5; $i++) {
    $packet = pack("C*", 25);
    $packet .= pack("C*", 1);
    stream_socket_sendto($socket, $packet, 0, $peer);

    sleep(1);
}
?>