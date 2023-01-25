<?php
if(strpos($_SERVER['REQUEST_URI'], "/HTTPTrigger") !== FALSE) {
    if($_SERVER["REQUEST_METHOD"] == "GET") {
        $name = "World";
        if(isset($_GET["name"])) {
            $name = $_GET["name"];
        }
        echo "Hello ".$name." from an Azure Function written in PHP!";
    }
    else if($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = "World";
        $json = file_get_contents('php://input');
        $jsonBody = json_decode($json, true);
        if(isset($jsonBody["name"])) {
            $name = $jsonBody["name"];
        }
        echo "Hello ".$name." from an Azure Function written in PHP!";
    }
}
else if(strpos($_SERVER['REQUEST_URI'], "/QueryTrigger") !== FALSE) {
    header("Content-Type: application/json");
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = "World";
        $json = file_get_contents('php://input');
        $jsonBody = json_decode($json, true);
        if(isset($jsonBody["name"])) {
            $name = $jsonBody["name"];
        }
        $message = "Hello ".$name." from an Azure Function written in PHP!";
        echo json_encode([
            "Outputs" => [
                "message" => $message,
                "res" => [
                    "statusCode" => 200,
                    "body" => $message
                ]
            ],
            "Logs" => [
                "Request completed"
            ]
        ]);
    }
}
else {
    phpinfo();
}