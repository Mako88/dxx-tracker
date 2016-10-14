<?php
$peer = $argv[1];

// Create socket
$socket = stream_socket_server("udp://0.0.0.0:9998", $errno, $errstr, STREAM_SERVER_BIND);

// Spit out an error if the socket couldn't be created
if (!$socket) {
    die("$errstr ($errno)");
}

// Send non hole-punch ACK
$packet = pack("C*", 25);
$packet .= pack("C*", 0);
for($i = 0; $i < 5; $i++) {
    stream_socket_sendto($socket, $packet, 0, $peer);
    sleep(1);
}

// Can't pass direct by reference, so use temp variables.
$read   = array($socket);
$write  = NULL;
$except = NULL;

// Check to see if we are sent anything
if(stream_select($read, $write, $except, 5))) {
    
    $pkt = stream_socket_recvfrom($socket, 99999, 0, $peer);
    
    // Send hole-punch ACK
    $packet = pack("C*", 25);
    $packet .= pack("C*", 1);
    for($i = 0; $i < 5; $i++) {
        stream_socket_sendto($socket, $packet, 0, $peer);
        sleep(1);
    }
}



if(!empty($pkt)) {
    
}

?>