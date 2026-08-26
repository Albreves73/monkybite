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

$ch = curl_init("https://monkybite.com/create-payment-link.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    "plan" => $plan,
    "email" => $email
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$checkoutUrl = curl_exec($ch);
curl_close($ch);

header("Location: " . $checkoutUrl);
exit();
?>
