<?php
// Test Client
$socket = stream_socket_client("udp://dxxtracker.hopto.org:9999", $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);

if (!$socket) {
    die("$errstr ($errno)");
}

while(1) {
    // This can be changed to send any opcode and key/info combinations desired
    $opcode = pack("C*", 21);
    
    $packed = pack("CSSSIICCCCCCCa*", 1,0,58,1,1234,3,4,0,3,0,2,8,0,"That Guy\x00That Mission\x00That Other Mission");
    
    
    $string = $opcode . "b=D1X,c=" . $packed;
    
    echo "Sending...\n";
    fwrite($socket, $string);
    
    // By default it sends a packet every 5 seconds
    sleep(5);
}

?>
