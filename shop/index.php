<?php
ob_start();
session_start();
include("../database/config.php");
include("../database/functions.php");
include("../database/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$message = '';

header("location: products.php");
?>