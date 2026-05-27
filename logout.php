<?php
session_start();
session_destroy();
header('Location: /mini-crm/login.php');
exit;
?>