<?php
session_start();
session_destroy();
header("Location: /unideportes-system/public/index.php");
exit();
?>