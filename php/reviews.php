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
    SELECT r.id, r.waardering, r.speelgoedId, r.gebruikerId, r.inhoud, r.datum 
    FROM vzwballonneke.review r
    JOIN vzwballonneke.speelgoed s ON s.id = r.speelgoedId
    WHERE s.doelgroepId = ?");
    mysqli_stmt_bind_param($sql, 'i', $_SESSION['sess_doelgroep']);
    mysqli_stmt_execute($sql);
    mysqli_stmt_store_result($sql);

    if (mysqli_stmt_num_rows($sql) > 0) {
        mysqli_stmt_bind_result($sql, $id, $waardering, $speelgoedId, $gebruikerId, $inhoud, $datum);
        $json = '{"ok": 1';
        $json .= ', "reviews": [';
        $first= true;
        while (mysqli_stmt_fetch($sql)) {
            if (!$first) {
                $json .= ', ';
            }
            $json .= '{"id": ' . $id;
            $json .= ', "waardering": ' . $waardering;
            $json .= ', "speelgoedId": ' . $speelgoedId;
            $json .= ', "gebruikerId": ' . $gebruikerId;
            $json .= ', "inhoud": "' . $inhoud . '"';
            $json .= ', "datum": "' . $datum . '"}';
            $first = false;
        }
        $json .= ']}';
    }
    mysqli_stmt_close($sql);
    mysqli_close($conn);
}

echo $json;


