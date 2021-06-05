<?php

session_start();
if(!isset($_SESSION['sess_user']) || $_SESSION['sess_role'] != "Admin"){
    $json = '{"ok": 0}';
} else {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

    // Set json output standard to failed
    $json = '{"ok": 0}';

    if ($contentType === "application/json") {
        //Receive the RAW post data.
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        //If json_decode failed, the JSON is invalid.
        if (is_array($decoded)) {
            // Get relevant values from task
            $id = $decoded["id"];
            $status = $decoded["status"];

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
            $sql = mysqli_prepare($conn,"UPDATE vzwballonneke.speelgoed SET specialeAanvraag = ? WHERE id = ?;");
            mysqli_stmt_bind_param($sql,'ii', $status, $id);

            if (mysqli_stmt_execute($sql)) {
                // Construct response
                $json = '{"ok": 1';
                $json .= ', "id": ' . $id;
                $json .= ', "status": ' . $status;
                $json .= '}';
            }
            mysqli_stmt_close($sql);
            mysqli_close($conn);
        }
    }
}

echo $json;

