<?php

function handleFile($type, $fileName, $fileTempName): string {
    switch ($type) {
        case "foto":
            $target_dir = "/images/";
            break;
        case "bijlage":
            $target_dir = "/bijlages/";
            break;
    }
    $resultPath = $target_dir . basename($fileName);
    if (move_uploaded_file($fileTempName, $_SERVER['DOCUMENT_ROOT'] . $resultPath))
        return $resultPath;
    return 0;
}

session_start();
if(!isset($_SESSION['sess_user']) || $_SESSION['sess_role'] != "Admin"){
    $json = '{"ok": 0}';
} else {
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        //Get relevant values
        $id = intval($_POST['id']);
        $foto = handleFile('foto', $_FILES['foto']['name'], $_FILES['foto']['tmp_name']);
        $titel = "" . $_POST['titel'];
        $oudCode = "" . $_POST['oudCode'] != "" ? $_POST['oudCode'] : null;
        $doelgroep = intval($_POST['doelgroep']);
        $leeftijd = $_POST['leeftijd'];
        $subCategorieen = $_POST['subCategorieen'];
        $merk = "" . $_POST['merk'] != "" ? $_POST['merk'] : null;
        $leverancier = "" . $_POST['leverancier'] != "" ? $_POST['leverancier'] : null;
        $aankoopDatum = "" . $_POST['aankoopDatum'] != "" ? $_POST['aankoopDatum'] : null;
        $inhoud = array();
        foreach ($_POST['inhoud'] as $item) {
            array_push($inhoud, json_decode($item, true));
        }
        unset($item);
        $tips = array();
        foreach ($_POST['tips'] as $item) {
            array_push($tips, json_decode($item, true));
        }
        unset($item);
        $korteInhoud = "" . $_POST['korteInhoud'] != "" ? $_POST['korteInhoud'] : null;
        $bijlages = array();
        for ($i = 0, $j = 0; $i < count($_POST['bijlagesNaam']); $i++){
            $bijlagesNaam = json_decode($_POST['bijlagesNaam'][$i], true);
            $path = 0;
            if (!$bijlagesNaam['original']) {
                $path = handleFile('bijlage', $_FILES['bijlagesBestand']['name'][$j], $_FILES['bijlagesBestand']['tmp_name'][$j]);
                $j++;
            }
            array_push($bijlages,
                [
                    'id' => $bijlagesNaam['id'],
                    'naam' => "" . $bijlagesNaam['naam'] != "" ? $bijlagesNaam['naam'] : "Bijlage",
                    'path' => $path,
                    'original' => intval($bijlagesNaam['original']),
                    'removed' => intval($bijlagesNaam['removed'])
                ]
            );
        }
        unset($item);
        $beschikbaar = intval($_POST['beschikbaar']);
        $specialeAanvraag = intval($_POST['specialeAanvraag']);

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

        //Get foto path en archief info
        $sql = mysqli_prepare($conn, "SELECT foto, isArchief, archiefDatum FROM vzwballonneke.speelgoed WHERE id = ?");
        mysqli_stmt_bind_param($sql, 'i',$id);
        mysqli_stmt_execute($sql);
        mysqli_stmt_store_result($sql);
        mysqli_stmt_bind_result($sql, $fotoDB, $isArchiefDB, $archiefDatumDB);
        if (mysqli_stmt_num_rows($sql) > 0) {
            mysqli_stmt_fetch($sql);
            $foto = $foto ? $foto : $fotoDB;
            $isArchief = $isArchiefDB ? 1 : 0;
            $archiefDatum = $archiefDatumDB ? $archiefDatumDB : "";
            mysqli_stmt_close($sql);
        }

        // Set query
        $sql = mysqli_prepare($conn,
            "
            UPDATE vzwballonneke.speelgoed 
            SET oudCode = ?,
                doelgroepId= ?,
                naam = ?,
                korteInhoud = ?,
                merk = ?,
                leverancier = ?,
                aankoopDatum = ?,
                foto = ?,
                beschikbaar = ?,
                specialeAanvraag = ?
            WHERE id = ?;"
        );
        mysqli_stmt_bind_param($sql, 'sissssssiii',
            $oudCode,
            $doelgroep,
            $titel,
            $korteInhoud,
            $merk,
            $leverancier,
            $aankoopDatum,
            $foto,
            $beschikbaar,
            $specialeAanvraag,
            $id
        );

        if (mysqli_stmt_execute($sql)) {
            mysqli_stmt_close($sql);
            //Inhoud
            $newInhoud = [];
            foreach ($inhoud as $item) {
                if ($item['original']) {
                    if ($item['removed']) {
                        $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.inhoud WHERE id = ?");
                        mysqli_stmt_bind_param($sql, 'i', $item['id']);
                        mysqli_stmt_execute($sql);
                        mysqli_stmt_close($sql);
                    } else {
                        $sql = mysqli_prepare($conn, "UPDATE vzwballonneke.inhoud SET inhoudItem = ?, aantal = ?, tekort = ?, kapot = ? WHERE id = ?");
                        mysqli_stmt_bind_param($sql, 'siiii', $item['text'], $item['aantal'], $item['tekort'], $item['kapot'], $item['id']);
                        if (mysqli_stmt_execute($sql)) {
                            mysqli_stmt_close($sql);
                            array_push($newInhoud, array(
                                    'id' => $item['id'],
                                    'text' => $item['text'],
                                    'aantal' => $item['aantal'],
                                    'tekort' => $item['tekort'],
                                    'kapot' => $item['kapot'])
                            );
                        }
                    }
                } else {
                    $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.inhoud (speelgoedId, inhoudItem, aantal, tekort, kapot) VALUES (?, ?, ?, ?, ?);");
                    mysqli_stmt_bind_param($sql, 'isiii', $id, $item['text'], $item['aantal'], $item['tekort'], $item['kapot']);
                    if (mysqli_stmt_execute($sql)) {
                        mysqli_stmt_close($sql);
                        array_push($newInhoud, array(
                                'id' => mysqli_insert_id($conn),
                                'text' => $item['text'],
                                'aantal' => $item['aantal'],
                                'tekort' => $item['tekort'],
                                'kapot' => $item['kapot'])
                        );
                    }
                }
            }
            unset($item);

            //Tips
            $newTips = [];
            foreach ($tips as $item) {
                if ($item['original']) {
                    if ($item['removed']) {
                        $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.tip WHERE id = ?");
                        mysqli_stmt_bind_param($sql, 'i', $item['id']);
                        mysqli_stmt_execute($sql);
                        mysqli_stmt_close($sql);
                    } else {
                        $sql = mysqli_prepare($conn, "UPDATE vzwballonneke.tip SET tipItem = ? WHERE id = ?");
                        mysqli_stmt_bind_param($sql, 'siiii', $item['text'], $item['id']);
                        if (mysqli_stmt_execute($sql)) {
                            mysqli_stmt_close($sql);
                            array_push($newTips, array(
                                    'id' => $item['id'],
                                    'text' => $item['text'])
                            );
                        }
                    }
                } else {
                    $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.tip (speelgoedId, tipItem) VALUES (?, ?);");
                    mysqli_stmt_bind_param($sql, 'is', $id, $item['text']);
                    if (mysqli_stmt_execute($sql)) {
                        mysqli_stmt_close($sql);
                        array_push($newTips, array(
                                'id' => mysqli_insert_id($conn),
                                'text' => $item['text'])
                        );
                    }
                }
            }
            unset($item);

            //Bijlages
            $newBijlages = [];
            foreach ($bijlages as $item) {
                if ($item['original']) {
                    if ($item['removed']) {
                        $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.bijlage WHERE id = ?");
                        mysqli_stmt_bind_param($sql, 'i', $item['id']);
                        mysqli_stmt_execute($sql);
                        mysqli_stmt_close($sql);
                    } else {
                        $sql = mysqli_prepare($conn, "UPDATE vzwballonneke.bijlage SET bijlageType = ? WHERE id = ?");
                        mysqli_stmt_bind_param($sql, 'si', $item['naam'], $item['id']);
                        if (mysqli_stmt_execute($sql)) {
                            mysqli_stmt_close($sql);

                            $sql = mysqli_prepare($conn, "SELECT bijlagePath FROM vzwballonneke.bijlage WHERE id = ?");
                            mysqli_stmt_bind_param($sql, 'i',$item['id']);
                            mysqli_stmt_execute($sql);
                            mysqli_stmt_store_result($sql);
                            mysqli_stmt_bind_result($sql, $itemPath);
                            if (mysqli_stmt_num_rows($sql) > 0) {
                                mysqli_stmt_fetch($sql);
                                array_push($newBijlages, array(
                                        'id' => $item['id'],
                                        'type' => $item['naam'],
                                        'path' => $itemPath)
                                );
                                mysqli_stmt_close($sql);
                            }
                        }
                    }
                } else if($item['path']) {
                    $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.bijlage (speelgoedId, bijlageType, bijlagePath) VALUES (?, ?, ?);");
                    mysqli_stmt_bind_param($sql, 'iss', $id, $item['naam'], $item['path']);
                    if (mysqli_stmt_execute($sql)) {
                        mysqli_stmt_close($sql);
                        array_push($newBijlages, array(
                                'id' => mysqli_insert_id($conn),
                                'type' => $item['naam'],
                                'path' => $item['path'])
                        );
                    }
                }
            }
            unset($item);

            //Delete all leeftijden en subCategorieen
            $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.speelgoed_subCategorie WHERE speelgoedId = ?");
            mysqli_stmt_bind_param($sql, 'i',$id);
            mysqli_stmt_execute($sql);
            mysqli_stmt_close($sql);

            // Leeftijden
            $leeftijdIds = [];
            foreach ($leeftijd as $item) {
                $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.speelgoed_subCategorie (speelgoedId, subCategorieId) VALUES (?, ?);");
                mysqli_stmt_bind_param($sql, 'ii', $id, $item);
                if (mysqli_stmt_execute($sql)) {
                    mysqli_stmt_close($sql);
                    array_push($leeftijdIds, intval($item));
                }
            }
            unset($item);

            // Subcategorieën
            $subCategorieIds = [];
            foreach ($subCategorieen as $item) {
                $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.speelgoed_subCategorie (speelgoedId, subCategorieId) VALUES (?, ?);");
                mysqli_stmt_bind_param($sql, 'ii', $id, $item);
                if (mysqli_stmt_execute($sql)) {
                    mysqli_stmt_close($sql);
                    array_push($subCategorieIds, intval($item));
                }
            }
            unset($item);

            //Construct response
            $json = '{"ok": 1';
            $json .= ', "doelgroep":' . $doelgroep;
            $json .= ', "speelgoed": {';
            $json .= '"id": ' . $id;
            $json .= ', "oudCode": "' . $oudCode . '"';
            $json .= ', "naam": "' . $titel . '"';
            $json .= ', "korteInhoud": "' . $korteInhoud . '"';
            $json .= ', "merk": "' . $merk . '"';
            $json .= ', "leverancier": "' . $leverancier . '"';
            $json .= ', "aankoopDatum": "' . $aankoopDatum . '"';
            $json .= ', "foto": "' . $foto . '"';
            $json .= ', "beschikbaar": ' . $beschikbaar;
            $json .= ', "specialeAanvraag": ' . $specialeAanvraag;
            $json .= ', "isArchief": ' . $isArchief;
            $json .= ', "archiefDatum": "' . $archiefDatum . '"';
            $json .= ', "inhoud": ' . json_encode($newInhoud);
            $json .= ', "tips": ' . json_encode($newTips);
            $json .= ', "bijlages": ' . json_encode($newBijlages);
            $json .= ', "leeftijden": ' . json_encode($leeftijdIds);
            $json .= ', "subCategorieen": ' . json_encode($subCategorieIds);
            $json .= '}';
            $json .= '}';
        }
        mysqli_close($conn);
    }
}

echo $json;
