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
            $categorieNaam = $decoded['categorieNaam'];
            $doelgroepId = $_SESSION['sess_doelgroep'];

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
            $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.categorie (naam, doelgroepId) VALUES (?, ?);");
            mysqli_stmt_bind_param($sql, 'si', $categorieNaam, $doelgroepId);
            if (mysqli_stmt_execute($sql)) {
                mysqli_stmt_close($sql);
                $id = mysqli_insert_id($conn);
                $json = '{"ok": 1';
                $json .= ', "categorie": {';
                $json .= '"id": ' . $id;
                $json .= ', "naam": "' . $categorieNaam . '"';
                $json .= ', "doelgroepId": ' . $doelgroepId;
                $json .= '}';
                $json .= '}';
            }
            mysqli_close($conn);
        }
    }
}

echo $json;