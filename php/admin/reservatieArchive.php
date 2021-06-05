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
            $ids = $decoded['ids'];
            $isArchief = $decoded['isArchief'] ? 1 : 0;
            $archiefDatum = $decoded['archiefDatum'];

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
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $types = str_repeat('i', count($ids));

            $sql = mysqli_prepare($conn, "UPDATE vzwballonneke.reservatie SET isArchief = ?, archiefDatum = ? WHERE id IN ($in);");
            mysqli_stmt_bind_param($sql, 'is'.$types,$isArchief, $archiefDatum, ...$ids);
            if (mysqli_stmt_execute($sql)) {
                $json = '{"ok": 1';
                $json .= ', "ids": ' . json_encode($ids);
                $json .= ', "isArchief": ' . $isArchief;
                $json .= ', "archiefDatum": "' . $archiefDatum . '"';
                $json .= '}';
            }
            mysqli_stmt_close($sql);
            mysqli_close($conn);
        }
    }
}

echo $json;




