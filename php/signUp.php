<?php
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
    //Receive the RAW post data.
    $content = trim(file_get_contents("php://input"));
    $decoded = json_decode($content, true);

    $json = '{"ok": 0}';

    //If json_decode failed, the JSON is invalid.
    if(is_array($decoded)) {
        //Get relevant values from task
        $user = $decoded["user"] ? $decoded["user"] : null;
        $email = $decoded["email"] ? $decoded["email"] : null;
        $pass = $decoded["encrpass"] ? $decoded["encrpass"] : null;
        $doelgroep = $decoded["doelgroep"] ? $decoded["doelgroep"] : null;

        //Connect to DB
        $servername = "localhost";
        $username = "vzwballonneke";
        $password = "RCwgd8bfh9Gu";
        $dbname = "vzwballonneke";

        // Create connection
        $conn = mysqli_connect($servername, $username, $password);

        // Check connection
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Set query
        $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.gebruiker (naam , email, wachtwoord, doelgroepId) VALUES (?, ?, ?, ?);");
        mysqli_stmt_bind_param($sql, 'sssi', $user, $email, $pass, $doelgroep);

        if (mysqli_stmt_execute($sql)) {
            $json = '{"ok": 1}';
        }

        mysqli_stmt_close($sql);
        mysqli_close($conn);
    }

    echo $json;
}
