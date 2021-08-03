<?php
session_start();
if(!isset($_SESSION['sess_user']) || ($_SESSION['sess_role'] != "Admin")){
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
        if(is_array($decoded)) {
            // Get relevant values from task
            $ids = $decoded["toDelete"];

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

            $success = true;
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $types = str_repeat('i', count($ids));

            // Delete query reservatie
            $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.reservatie WHERE periodeId IN ($in);");
            mysqli_stmt_bind_param($sql, $types, ...$ids);
            if (!mysqli_stmt_execute($sql)) {
                $success = false;
            }
            mysqli_stmt_close($sql);

            // Delete query periode
            $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.periode WHERE id IN ($in);");
            mysqli_stmt_bind_param($sql, $types, ...$ids);
            if (!mysqli_stmt_execute($sql)) {
                $success = false;
            }

            if ($success) {
                $json = '{"ok": 1';
                $json .= ', "ids": ' . json_encode($ids);
                $json .= '}';
            }
            mysqli_stmt_close($sql);
            mysqli_close($conn);
        }
    }
}
echo $json;



