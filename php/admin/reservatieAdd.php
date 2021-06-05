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
            $gebruikerId = $decoded['gebruikerId'];
            $speelgoedId = $decoded['speelgoedId'];
            $periodeId = $decoded['periodeId'];

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
            $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.reservatie (speelgoedId, gebruikerId, periodeId) VALUES (?, ?, ?);");
            mysqli_stmt_bind_param($sql, 'iii', $speelgoedId, $gebruikerId, $periodeId);
            if (mysqli_stmt_execute($sql)) {
                $id = mysqli_insert_id($conn);
                $json = '{"ok": 1';
                $json .= ', "reservatie": {';
                $json .= '"id": ' . $id;
                $json .= ', "speelgoedId": ' . $speelgoedId;
                $json .= ', "gebruikerId": ' . $gebruikerId;
                $json .= ', "periodeId": ' . $periodeId;
                $json .= ', "bevestigd": 0';
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


