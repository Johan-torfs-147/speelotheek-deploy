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
            $id = $decoded['id'];
            $naam = $decoded['naam'];
            $email = $decoded['email'];
            $wachtwoord = $decoded['wachtwoord'];
            $role = $decoded['role'] ? $decoded['role'] : "Niet bevestigd";
            $isArchief = $decoded['isArchief'] ? 1 : 0;

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
            if ($wachtwoord) {
                $sql = mysqli_prepare($conn, "UPDATE vzwballonneke.gebruiker SET naam = ?, email = ?, wachtwoord = ? WHERE id = ?;");
                mysqli_stmt_bind_param($sql, 'sssi',$naam, $email, $wachtwoord, $id);
            } else {
                $sql = mysqli_prepare($conn, "UPDATE vzwballonneke.gebruiker SET naam = ?, email = ? WHERE id = ?;");
                mysqli_stmt_bind_param($sql, 'ssi',$naam, $email, $id);
            }

            if (mysqli_stmt_execute($sql)) {
                $json = '{"ok": 1';
                $json .= ', "gebruiker": {';
                $json .= '"id": ' . $id;
                $json .= ', "doelgroepId": ' . $_SESSION['sess_doelgroep'];
                $json .= ', "naam": "' . $naam . '"';
                $json .= ', "email": "' . $email . '"';
                $json .= ', "role": "' . $role . '"';
                $json .= ', "isArchief": ' . $isArchief;
                $json .= '}';
                $json .= '}';
            }
            mysqli_stmt_close($sql);
            mysqli_close($conn);
        }
    }
}
echo $json;






