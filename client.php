<?php
// Test Client
$socket = stream_socket_client("udp://127.0.0.1:9999", $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);

if (!$socket) {
    die("$errstr ($errno)");
}

while(1) {
    // This can be changed to send any opcode and key/info combinations desired
    $opcode = pack("C*", 21);
    $string = $opcode . "a=1234,b=d1x,c=" . pack("a*", '1234lkqw90f8upq283rjkjr34asldkfj');
    echo "Sending...\n";
    fwrite($socket, $string);
    
    // By default it sends a packet every 5 seconds
    sleep(5);
}

?>
