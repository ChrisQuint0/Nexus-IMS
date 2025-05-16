<?php 
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

error_log("check_session.php executed");

if (isset($_SESSION['logged_in'])) {
    error_log("logged_in is set: " . $_SESSION['logged_in']);
} else {
    error_log("logged_in is NOT set");
}

if (isset($_SESSION['username'])) {
    error_log("username is set: " . $_SESSION['username']);
} else {
    error_log("username is NOT set");
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['username'])) {
    echo json_encode(['logged_in' => true, 'username' => $_SESSION['username']]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>