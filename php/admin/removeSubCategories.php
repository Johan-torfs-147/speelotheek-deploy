<?php
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

session_start();
if(!isset($_SESSION['sess_user']) || $_SESSION['sess_role'] != "Admin"){
    // Set json output standard to failed
    $json = '{"ok": 0}';
} else {
    if ($contentType === "application/json") {
        //Receive the RAW post data.
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        // Set json output standard to failed
        $check = 0;
        $categorieRemoved = 0;

        //If json_decode failed, the JSON is invalid.
        if(is_array($decoded)) {
            // Get relevant values from task
            $subCategorieIds = $decoded['subCategorieIds'];
            $removeCategorie = $decoded['removeCategorie'];
            $categorieId = $decoded['categorieId'];

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

            // Delete for every given ID
            $removedIds = array();
            foreach ($subCategorieIds as $id) {
                $first = true;
                $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.speelgoed_subCategorie WHERE subCategorieId = ?;");
                mysqli_stmt_bind_param($sql, 'i', $id);
                if (mysqli_stmt_execute($sql)) {
                    mysqli_stmt_close($sql);
                    $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.subCategorie WHERE id = ?;");
                    mysqli_stmt_bind_param($sql, 'i', $id);

                    if (mysqli_stmt_execute($sql)) {
                        mysqli_stmt_close($sql);
                        array_push($removedIds, $id);
                        $check = 1;
                        $first = false;
                    }
                }
            }

            // Delete categorie if requested
            if ($removeCategorie) {
                $sql = mysqli_prepare($conn, "DELETE FROM vzwballonneke.categorie WHERE id = ?;");
                mysqli_stmt_bind_param($sql, 'i', $categorieId);
                if (mysqli_stmt_execute($sql)) {
                    mysqli_stmt_close($sql);
                    $categorieRemoved = 1;
                    $check = 1;
                }
            }
            mysqli_close($conn);

            $json = '{"ok": ' . $check;
            $json .= ', "removedSubCategorieIds": ' . json_encode($removedIds);
            $json .= ', "categorieRemoved": ' . $categorieRemoved;
            $json .= '}';
        }
    }
}

echo $json;
