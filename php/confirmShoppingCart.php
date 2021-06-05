<?php
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

session_start();
if(!isset($_SESSION['sess_user']) || ($_SESSION['sess_role'] != "Admin" && $_SESSION['sess_role'] != "Gebruiker")){
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
            $winkelWagen = $decoded['winkelWagen'];

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

            $reservaties = [];
            $doubles = [];
            foreach ($winkelWagen as $item) {
                if ($_SESSION['user_id'] == $item['gebruikerId']) {
                    // Check if item in reservaties
                    $sql = mysqli_prepare($conn, "SELECT count(*) FROM vzwballonneke.reservatie WHERE speelgoedId = ? AND periodeId = ?;");
                    mysqli_stmt_bind_param($sql, 'ii', $item['speelgoedId'], $item['periodeId']);
                    if (mysqli_stmt_execute($sql)) {
                        mysqli_stmt_bind_result($sql, $amount);
                        mysqli_stmt_fetch($sql);
                        if ($amount == 0) {
                            mysqli_stmt_close($sql);
                            // Insert into reservaties
                            $sql = mysqli_prepare($conn, "INSERT INTO vzwballonneke.reservatie (speelgoedId, gebruikerId, periodeId) VALUES (?, ?, ?);");
                            mysqli_stmt_bind_param($sql, 'iii', $item['speelgoedId'], $item['gebruikerId'], $item['periodeId']);
                            if (mysqli_stmt_execute($sql)) {
                                $id = mysqli_insert_id($conn);
                                array_push($reservaties, array(
                                    "id" => $id,
                                    "speelgoedId" => $item['speelgoedId'],
                                    "gebruikerId" => $item['gebruikerId'],
                                    "periodeId" => $item['periodeId'],
                                    "bevestigd" => 0,
                                    "isArchief" => 0
                                ));
                            } else {
                                array_push($doubles, array(
                                    "speelgoedId" => $item['speelgoedId'],
                                    "gebruikerId" => $item['gebruikerId'],
                                    "periodeId" => $item['periodeId']
                                ));
                            }
                            mysqli_stmt_close($sql);
                        } else {
                            mysqli_stmt_close($sql);
                            array_push($doubles, array(
                                "speelgoedId" => $item['speelgoedId'],
                                "gebruikerId" => $item['gebruikerId'],
                                "periodeId" => $item['periodeId']
                            ));
                        }
                    } else {
                        mysqli_stmt_close($sql);
                        array_push($doubles, array(
                            "speelgoedId" => $item['speelgoedId'],
                            "gebruikerId" => $item['gebruikerId'],
                            "periodeId" => $item['periodeId']
                        ));
                    }
                }
            }
            unset($item);
            mysqli_close($conn);

            $json = '{"ok": 1';
            $json .= ', "reservaties": ' . json_encode($reservaties);
            $json .= ', "doubles": ' . json_encode($doubles);
            $json .= '}';

//            $json = '{"ok": 0';
//            $json .= ', "id": ' . json_encode($winkelWagen);
//            $json .= '}';
        }
    }
}

echo $json;


