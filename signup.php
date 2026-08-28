<?php
session_start();

$name = $_POST["name"];
$email = $_POST["email"];
$password = $_POST["password"];
$plan = $_POST["plan"];

$_SESSION["pending_user"] = [
    "name" => $name,
    "email" => $email,
    "password" => $password,
    "plan" => $plan
];

header("Location: checkout.php?plan=$plan&email=$email&name=$name&password=$password");
exit();
?>
