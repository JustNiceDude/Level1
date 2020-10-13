<?php
// не обращайте на эту функцию внимания
// она нужна для того чтобы правильно считать входные данные
function readHttpLikeInput()
{
    $f = fopen('php://stdin', 'r');
    $store = "";
    $toread = 0;
    while ($line = fgets($f)) {
        $store .= preg_replace("/\r/", "", $line);
        if (preg_match('/Content-Length: (\d+)/', $line, $m))
            $toread = $m[1] * 1;
        if ($line == "\r\n")
            break;
    }
    if ($toread > 0)
        $store .= fread($f, $toread);
    return $store;
}

$contents = readHttpLikeInput();

function parseTcpStringAsHttpRequest($string)
{
    $splittedInputString = explode(" ", $string);   // Split input string by space to get "method" and "uri"
    $splittedInputString2 = explode("\n", $string); // Split input string by "\n" to get "headers" and "body"
    // Get headers from request input string
    $headersArray = array();
    $i = 1; //Skips the "method" and "uri" parts
    while ($splittedInputString2[$i] != "") {
        $partsOfCurrentHeader = explode(": ", $splittedInputString2[$i]);
        $restoredCurrentHeader = array($partsOfCurrentHeader[0], $partsOfCurrentHeader[1]);
        $headersArray[] = $restoredCurrentHeader;
        $i++;
    }
    $body = end($splittedInputString2); // Get "body"-part from splitted input string
    return array(
        "method" => $splittedInputString[0],
        "uri" => $splittedInputString[1],
        "headers" => $headersArray,
        "body" => $body,
    );
}

$http = parseTcpStringAsHttpRequest($contents);
echo(json_encode($http, JSON_PRETTY_PRINT));