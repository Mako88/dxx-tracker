<?php

$socket = stream_socket_client("udp://127.0.0.1:9999", $errno, $errstr, STREAM_CLIENT_ASYNC_CONNECT);

if (!$socket) {
    die("$errstr ($errno)");
}

do {
    echo "Enter a message to send: ";
    $input = fgets(STDIN);
    
    $input = trim($input);
    
    fwrite($socket, $input);
    
    if($input == "call") {
        echo fread($socket, 1024) . "\n";
    }
} while (1);


?>