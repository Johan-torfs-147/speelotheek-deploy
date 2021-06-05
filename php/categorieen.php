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

    // Create connection
    $conn = mysqli_connect($servername, $username, $password);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Set query
    $sql = mysqli_prepare($conn, "
    SELECT c.id as cId, c.naam as cNaam, sc.id as scId, sc.naam as scNaam 
    FROM vzwballonneke.categorie c 
        LEFT JOIN vzwballonneke.subCategorie sc 
            ON c.id = sc.categorieId 
    WHERE doelgroepId = ? 
    ORDER BY c.id;");
    mysqli_stmt_bind_param($sql, 'i', $_SESSION['sess_doelgroep']);
    mysqli_stmt_execute($sql);
    mysqli_stmt_store_result($sql);

    $json = '{"ok": 1';
    if (mysqli_stmt_num_rows($sql) > 0) {
        mysqli_stmt_bind_result($sql, $cId, $cNaam, $scId, $scNaam);

        $json .= ', "categorieen": [';
        $first= true;
        $row = mysqli_stmt_fetch($sql);
        while ($row) {
            if (!$first) {
                $json .= ', ';
            }
            $currentCId = $cId;
            $json .= '{"id": ' . $cId;
            $json .= ', "naam": "' . $cNaam . '"';
            $json .= ', "subCategorieen": [';
            $firstL2= true;
            while ($row && $currentCId == $cId) {
                if ($scId != null) {
                    if (!$firstL2) {
                        $json .= ', ';
                    }
                    $json .= '{"id": ' . $scId;
                    $json .= ', "naam": "' . $scNaam . '"}';
                }
                $row = mysqli_stmt_fetch($sql);
                $firstL2 = false;
            }
            $json .= ']}';
            $first = false;
        }
        $json .= ']';
    }
    $json .= '}';
    mysqli_stmt_close($sql);
    mysqli_close($conn);
}

echo $json;
