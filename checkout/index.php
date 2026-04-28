<?php
$plan = $_GET['plan'] ?? '';
$email = $_GET['email'] ?? '';
header("Location: /checkout.html?plan=$plan&email=$email");
exit;
?>
