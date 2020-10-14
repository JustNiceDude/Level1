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

function outputHttpResponse($statuscode, $statusmessage, $headers, $body)
{
    echo "HTTP/1.1 " . $statuscode . " " . $statusmessage . "\n";
    echo "Date: " . date(DATE_RFC822) . "\n";
    for ($i = 0; $i < count($headers); $i++) {
        echo $headers[$i][0] . ": " . $headers[$i][1] . "\n";
    }
    echo "\n" . $body;
}

function processHttpRequest($method, $uri, $headers, $body)
{
    // Initialise constants for response
    define("GET", "GET");
    define("SUM", "sum");
    define("NUMS", "nums");
    $messagesList = array("OK", "Not Found", "Bad Request");
    $statuscode = 200;
    $statusmessage = $messagesList[0];
    if ($method == GET) {
        preg_match_all("!\d+!", $uri, $numbers); //the sum of number is body.... I guess
        unset($body);
        $body = array_sum($numbers[0]);
        if (strpos($uri, SUM) == false) {
            $statuscode = 404;
            $statusmessage = $messagesList[1];
            $body = strtolower($messagesList[1]);
        }

        if (strpos($uri, NUMS) == false) {
            $statuscode = 400;
            $statusmessage = $messagesList[2];
            $body = strtolower($messagesList[2]);
        }
    } else {
        $statuscode = 400;
        $statusmessage = $messagesList[2];
        $body = strtolower($messagesList[2]);
    }

    unset($headers);
    $headers[] = array("Server","Apache/2.2.14 (Win32)");
    $headers[] = array("Connection","Closed");
    $headers[] = array("Content-Type", "text/html; charset=utf-8");
    $headers[] = array("Content-Length", strlen($body));


    outputHttpResponse($statuscode, $statusmessage, $headers, $body);
}


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
processHttpRequest($http["method"], $http["uri"], $http["headers"], $http["body"]);
