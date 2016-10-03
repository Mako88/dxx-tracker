<?php
// Test Client
$socket = stream_socket_client("udp://127.0.0.1:9999", $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);

if (!$socket) {
    die("$errstr ($errno)");
}

while(1) {
    $opcode = pack("C*", 21);
    $string = $opcode . "a=1234,b=d1x,c=" . pack("a*", '1234lkqw90f8upq283rjkjr34asldkfj');
    echo "Sending...\n";
    fwrite($socket, $string);
    
    sleep(5);
}

?>
