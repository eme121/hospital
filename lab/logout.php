<?php
session_start();
session_destroy();
header('Location: ../staff_portal.php');
exit;
?>