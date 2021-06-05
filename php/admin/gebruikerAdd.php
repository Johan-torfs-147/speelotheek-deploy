<?php
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

session_start();
if(!isset($_SESSION['sess_user']) || $_SESSION['sess_role'] != "Admin"){
    $json = '{"ok": 0}';
} else {
    if ($contentType === "application/json") {
        //Receive the RAW post data.
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        // Set json output standard to failed
        $json = '{"ok": 0}';

        //If json_decode failed, the JSON is invalid.
        if(is_array($decoded)) {
            // Get relevant values from task
            $naam = $decoded['naam'];
            $email = $decoded['email'];
            $wachtwoord = $decoded['wachtwoord'];

            // Connect to DB
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
            mysqli_stmt_bind_param($sql, 'sssi', $naam, $email, $wachtwoord, $_SESSION['sess_doelgroep']);
            if (mysqli_stmt_execute($sql)) {
                $id = mysqli_insert_id($conn);
                $json = '{"ok": 1';
                $json .= ', "gebruiker": {';
                $json .= '"id": ' . $id;
                $json .= ', "naam": "' . $naam . '"';
                $json .= ', "doelgroepId": ' . $_SESSION['sess_doelgroep'];
                $json .= ', "email": "' . $email . '"';
                $json .= ', "role": "Niet bevestigd"';
                $json .= ', "isArchief": 0';
                $json .= '}';
                $json .= '}';
            }
            mysqli_stmt_close($sql);
            mysqli_close($conn);
        }
    }
}

echo $json;


