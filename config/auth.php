<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /mini-crm/login.php');
    exit;
}
?>