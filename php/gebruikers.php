<?php
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

session_start();
if(!isset($_SESSION['sess_user']) || ($_SESSION['sess_role'] != "Admin" && $_SESSION['sess_role'] != "Gebruiker")){
    $json = '{"ok": 0}';
} else {
    // Connect to DB
    $servername = "localhost";
    $username = "vzwballonneke";
    $password = "RCwgd8bfh9Gu";
    $dbname = "vzwballonneke";

    $json = '{"ok": 0}';

    // Create connection
    $conn = mysqli_connect($servername, $username, $password);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Set query
    $sql = mysqli_prepare($conn, "
    SELECT id, naam, email, rol, isArchief, doelgroepId 
    FROM vzwballonneke.gebruiker");
    mysqli_stmt_execute($sql);
    mysqli_stmt_store_result($sql);

    if (mysqli_stmt_num_rows($sql) > 0) {
        mysqli_stmt_bind_result($sql, $resId, $resNaam, $resEmail, $resRol, $resArchief, $doelgroepId);
        $json = '{"ok": 1';
        $json .= ', "gebruikers": [';
        $first= true;
        while (mysqli_stmt_fetch($sql)) {
            if (!$first) {
                $json .= ', ';
            }
            $json .= '{"id": ' . $resId;
            $json .= ', "naam": "' . $resNaam . '"';
            $json .= ', "doelgroepId": ' . $doelgroepId;
            if ($_SESSION['sess_role'] == "Admin") {
                $json .= ', "email": "' . $resEmail . '"';
                $json .= ', "role": "' . $resRol . '"';
                $json .= ', "isArchief": ' . $resArchief;
            }
            $json .= "}";
            $first = false;
        }
        $json .= ']}';
    }
    mysqli_stmt_close($sql);
    mysqli_close($conn);
}

echo $json;


