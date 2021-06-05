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
    SELECT id, oudCode, naam, korteInhoud, merk, aankoopDatum, foto, beschikbaar, specialeAanvraag, isArchief, archiefDatum  
    FROM vzwballonneke.speelgoed 
    WHERE doelgroepId = ?;");
    mysqli_stmt_bind_param($sql, 'i', $_SESSION['sess_doelgroep']);
    mysqli_stmt_execute($sql);
    mysqli_stmt_store_result($sql);

    $json = '{"ok": 1';
    if (mysqli_stmt_num_rows($sql) > 0) {
        mysqli_stmt_bind_result($sql, $id, $oudCode, $naam, $korteInhoud, $merk, $aankoopDatum, $foto, $beschikbaar, $specialeAanvraag, $isArchief, $archiefDatum);

        $json .= ', "speelgoed": [';
        $first= true;
        while ($row = mysqli_stmt_fetch($sql)) {
            if (!$first) {
                $json .= ', ';
            }
            $json .= '{"id": ' . $id;
            $json .= ', "oudCode": "' . $oudCode . '"';
            $json .= ', "naam": "' . $naam . '"';
            $json .= ', "korteInhoud": "' . $korteInhoud . '"';
            $json .= ', "merk": "' . $merk . '"';
            $json .= ', "aankoopDatum": "' . $aankoopDatum . '"';
            $json .= ', "foto": "' . $foto . '"';
            $json .= ', "beschikbaar": ' . $beschikbaar;
            $json .= ', "specialeAanvraag": ' . $specialeAanvraag;
            $json .= ', "isArchief": ' . $isArchief;
            $json .= ', "archiefDatum": "' . $archiefDatum . '"';

            // Get inhoud
            $json .= ', "inhoud": [';
            $itemSql = mysqli_prepare($conn, "SELECT id, inhoudItem, aantal, tekort, kapot FROM vzwballonneke.inhoud WHERE speelgoedId = ?");
            mysqli_stmt_bind_param($itemSql, 'i', $id);
            mysqli_stmt_execute($itemSql);
            mysqli_stmt_store_result($itemSql);
            if (mysqli_stmt_num_rows($itemSql) > 0) {
                mysqli_stmt_bind_result($itemSql, $itemId, $itemText, $itemAantal, $itemTekort, $itemKapot);
                $first = true;
                while ($itemRow = mysqli_stmt_fetch($itemSql)) {
                    if (!$first) {
                        $json .= ', ';
                    }
                    $json .= '{"id": ' . $itemId;
                    $json .= ', "text": "' . $itemText . '"';
                    $json .= ', "aantal": ' . $itemAantal;
                    $json .= ', "tekort": ' . $itemTekort;
                    $json .= ', "kapot": ' . $itemKapot;
                    $json .= '}';
                    $first = false;
                }
            }
            $json .= ']';
            mysqli_stmt_close($itemSql);

            // Get tips
            $json .= ', "tips": [';
            $itemSql = mysqli_prepare($conn, "SELECT id, tipItem FROM vzwballonneke.tip WHERE speelgoedId = ?");
            mysqli_stmt_bind_param($itemSql, 'i', $id);
            mysqli_stmt_execute($itemSql);
            mysqli_stmt_store_result($itemSql);
            if (mysqli_stmt_num_rows($itemSql) > 0) {
                mysqli_stmt_bind_result($itemSql, $itemId, $itemText);
                $first = true;
                while ($itemRow = mysqli_stmt_fetch($itemSql)) {
                    if (!$first) {
                        $json .= ', ';
                    }
                    $json .= '{"id": ' . $itemId;
                    $json .= ', "text": "' . $itemText . '"';
                    $json .= '}';
                    $first = false;
                }
            }
            $json .= ']';
            mysqli_stmt_close($itemSql);

            // Get bijlages
            $json .= ', "bijlages": [';
            $itemSql = mysqli_prepare($conn, "SELECT id, bijlageType, bijlagePath FROM vzwballonneke.bijlage WHERE speelgoedId = ?");
            mysqli_stmt_bind_param($itemSql, 'i', $id);
            mysqli_stmt_execute($itemSql);
            mysqli_stmt_store_result($itemSql);
            if (mysqli_stmt_num_rows($itemSql) > 0) {
                mysqli_stmt_bind_result($itemSql, $itemId, $itemType, $itemPath);
                $first = true;
                while ($itemRow = mysqli_stmt_fetch($itemSql)) {
                    if (!$first) {
                        $json .= ', ';
                    }
                    $json .= '{"id": ' . $itemId;
                    $json .= ', "type": "' . $itemType . '"';
                    $json .= ', "path": "' . $itemPath . '"';
                    $json .= '}';
                    $first = false;
                }
            }
            $json .= ']';
            mysqli_stmt_close($itemSql);

            // Get leeftijden en subCategorieen in één Array
            $json .= ', "subCategorieen": [';
            $itemSql = mysqli_prepare($conn, "SELECT subCategorieId FROM vzwballonneke.speelgoed_subCategorie WHERE speelgoedId = ?");
            mysqli_stmt_bind_param($itemSql, 'i', $id);
            mysqli_stmt_execute($itemSql);
            mysqli_stmt_store_result($itemSql);
            if (mysqli_stmt_num_rows($itemSql) > 0) {
                mysqli_stmt_bind_result($itemSql, $itemId);
                $first = true;
                while ($itemRow = mysqli_stmt_fetch($itemSql)) {
                    if (!$first) {
                        $json .= ', ';
                    }
                    $json .= $itemId;
                    $first = false;
                }
            }
            $json .= ']';
            mysqli_stmt_close($itemSql);

            $json .= '}';
            $first = false;
        }
        $json .= ']';
    }
    $json .= '}';
    mysqli_stmt_close($sql);
    mysqli_close($conn);
}

echo $json;
