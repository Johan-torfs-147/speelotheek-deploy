<?php
$json = '{"ok": 0}';

//Connect to DB
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
$sql = mysqli_prepare($conn, "SELECT id, naam, isArchief FROM vzwballonneke.doelgroep");
mysqli_stmt_execute($sql);
mysqli_stmt_store_result($sql);

$json = '{"ok": '.mysqli_stmt_num_rows($sql).'}';

if (mysqli_stmt_num_rows($sql) > 0) {
    mysqli_stmt_bind_result($sql, $resId, $resNaam, $resArchief);
    $json = '{"ok": 1';
    $json .= ', "doelgroepen": [';
    $first= true;
    while (mysqli_stmt_fetch($sql)) {
        if (!$first) {
            $json .= ', ';
        }
        $json .= '{"id": ' . $resId;
        $json .= ', "naam": "' . $resNaam . '"';
        $json .= ', "isArchief": ' . $resArchief . '}';
        $first = false;
    }
    $json .= ']}';

}
mysqli_stmt_close($sql);
mysqli_close($conn);

echo $json;
