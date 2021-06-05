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
            $start = $decoded['nieuwePeriodeStart'];
            $eind = $decoded['nieuwePeriodeEind'];

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
            $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.periode (startDatum, eindDatum, doelgroepId) VALUES (?, ?, ?);");
            mysqli_stmt_bind_param($sql, 'ssi', $start, $eind, $_SESSION['sess_doelgroep']);
            if (mysqli_stmt_execute($sql)) {
                $id = mysqli_insert_id($conn);
                $json = '{"ok": 1';
                $json .= ', "periode": {';
                $json .= '"id": ' . $id;
                $json .= ', "startDatum": "' . $start . '"';
                $json .= ', "eindDatum": "' . $eind . '"';
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


