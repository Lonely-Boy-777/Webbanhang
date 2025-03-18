<?php
session_start();
if (isset($_SESSION['username'])) {
    if ($_SESSION['role'] == 'Admin') {
    } elseif ($_SESSION['role'] == 'Member') {
    }
} else {
    header("Location: login.php");
    exit;
}
?>
