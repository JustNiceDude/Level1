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
    for ($i = 0; $i < count($headers); $i++) {
        echo $headers[$i][0] . ": " . $headers[$i][1] . "\n";
    }
    echo "\n" . $body;
}

function processHttpRequest($method, $uri, $headers, $body)
{
    // Initialise constants for checking response
    define("POST", "POST");
    define("URI", "/api/checkLoginAndPassword");
    define("CONTENT_TYPE", "application/x-www-form-urlencoded");
    define("BODY", "<h1 style=\"color:green\">FOUND</h1>");
    $messagesList = array("OK", "Bad Request", "Internal Server Error", "Login And Password Not Found");
    $statuscode = 200;
    $statusmessage = $messagesList[0];
    if ($method == POST && $uri == URI) {
        $splittedBody = explode("&", $body);
        $login = explode("=", $splittedBody[0])[1];    //Get login
        $password = explode("=", $splittedBody[1])[1]; //Get password
        $inputtedData = $login . ":" . $password;

        if (file_get_contents("password.txt") == false) {   //If file doesn't exist
            $statuscode = 500;
            $statusmessage = $messagesList[2];
            $body = strtolower($messagesList[2]);
        } else {
            $dataBase = explode("\n", file_get_contents("password.txt")); // Gets content from file
            if (array_search($inputtedData, $dataBase) == true) {
                $body = BODY;
            } else {
                $statuscode = 404;
                $statusmessage = $messagesList[3];
                $body = strtolower($messagesList[3]);
            }
        }
    } else {
        $statuscode = 400;
        $statusmessage = $messagesList[1];
        $body = strtolower($messagesList[1]);
    }
    // Refilling variable "$headers" that already exists with new data
    unset($headers);
    $headers[] = array("Server", "Apache/2.2.14 (Win32)");
    $headers[] = array("Content-Length", strlen($body));
    $headers[] = array("Connection", "Closed");
    $headers[] = array("Content-Type", "text/html; charset=utf-8");

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
