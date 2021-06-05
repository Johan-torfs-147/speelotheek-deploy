<?php
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
    //Receive the RAW post data.
    $content = trim(file_get_contents("php://input"));
    $decoded = json_decode($content, true);

    // Set json output standard to failed
    $json = '{"ok": 0}';

    //If json_decode failed, the JSON is invalid.
    if(is_array($decoded)) {
        // Get relevant values from task
        $user = $decoded["user"];
        $pass = $decoded["encrpass"];
        $doelgroep = $decoded["doelgroep"];

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
        $sql = mysqli_prepare($conn, "SELECT id, naam, email, doelgroepId, rol FROM vzwballonneke.gebruiker
                WHERE (naam = ? OR email = ?) AND wachtwoord = ? AND rol IN ('Gebruiker', 'Admin') AND isArchief = 0");
        mysqli_stmt_bind_param($sql, 'sss', $user, $user, $pass);
        mysqli_stmt_execute($sql);
        mysqli_stmt_store_result($sql);

        if (mysqli_stmt_num_rows($sql) > 0) {
            mysqli_stmt_bind_result($sql, $id, $resNaam, $resEmail, $resDoelgroep, $resRol);
            mysqli_stmt_fetch($sql);

            if ($resRol == "Admin" || $resDoelgroep == $decoded['doelgroep']) {
                session_start();
                $_SESSION['user_id']=$id;
                $_SESSION['sess_user']=$resNaam;
                $_SESSION['sess_role']=$resRol;
                $_SESSION['sess_doelgroep']=$decoded['doelgroep'];
                $_SESSION['doelgroep']=$resDoelgroep;
                $_SESSION['email']=$resEmail;

                $json = '{"ok": 1';
                $json .= ', "userData": {';
                $json .= '"user":"' . $_SESSION['sess_user'] . '"';
                $json .= ', "role":"' . $_SESSION['sess_role'] . '"';
                $json .= ', "sessDoelgroep":' . $_SESSION['sess_doelgroep'];
                $json .= ', "doelgroep":' . $_SESSION['doelgroep'];
                $json .= ', "email":"' . $_SESSION['email'] . '"';
                $json .= ', "isLoggedIn":' . true;
                $json .= ', "id":' . $id;
                $json .= '}';
                $json .= '}';
            } else {
                $json = '{"ok": 0';
                $json .= ', "errMsg": "U bent niet bevoegd om in te loggen bij deze doelgroep. Gelieve in te loggen bij uw eigen doelgoep."' ;
                $json .= '}';
            }
        }
        mysqli_stmt_close($sql);
        mysqli_close($conn);
    }
    echo $json;
}

