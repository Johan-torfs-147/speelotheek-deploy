<?php

session_start();
if(!isset($_SESSION['sess_user'])){
    $json = '{"ok": 0}';
} else {
    $json = '{"ok": 1';
    $json .= ', "userData": {';
    $json .= '"user":"' . $_SESSION['sess_user'] . '"';
    $json .= ', "role":"' . $_SESSION['sess_role'] . '"';
    $json .= ', "sessDoelgroep":' . $_SESSION['sess_doelgroep'];
    $json .= ', "doelgroep":' . $_SESSION['doelgroep'];
    $json .= ', "email":"' . $_SESSION['email'] . '"';
    $json .= ', "isLoggedIn":' . true;
    $json .= '}';
    $json .= '}';
}

echo $json;