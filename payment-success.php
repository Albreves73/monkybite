<?php
session_start();

if (!isset($_SESSION["pending_user"])) {
    die("No user data found. Please contact support.");
}

$user = $_SESSION["pending_user"];
$name = $user["name"];
$email = $user["email"];
$password = $user["password"];
$plan = $user["plan"];

$NEXTCLOUD_BASE_URL = "https://cloud.monkybite.com";
$ADMIN_USER = "admin";
$ADMIN_PASS = "YOUR_ADMIN_PASSWORD";

$displayName = $name;

$endpoint = $NEXTCLOUD_BASE_URL . "/ocs/v1.php/cloud/users";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_USERPWD        => $ADMIN_USER . ":" . $ADMIN_PASS,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        "userid"      => $email,
        "password"    => $password,
        "displayName" => $displayName
    ],
    CURLOPT_HTTPHEADER     => ["OCS-APIRequest: true"],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
curl_close($ch);

unset($_SESSION["pending_user"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful — MonkyBite</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- HEADER -->
<header>
  <a href="index.html" class="logo">
    <img src="logo.png" alt="MonkyBite Logo" />
    <span class="brand-name">MonkyBite</span>
  </a>

  <div class="nav-wrapper">
    <nav class="nav-desktop">
      <a href="index.html">HOME</a>
      <a href="login.html">LOGIN</a>
      <a href="plans.html">SIGN UP</a>
      <a href="contact.html">CONTACT</a>
    </nav>

    <button class="hamburger" id="hamburger-btn">☰</button>
    <nav class="nav-mobile hidden" id="mobile-nav">
      <a href="index.html">HOME</a>
      <a href="login.html">LOGIN</a>
      <a href="plans.html">SIGN UP</a>
      <a href="contact.html">CONTACT</a>
    </nav>
  </div>
</header>

<div class="go-back-wrapper">
  <a href="javascript:history.back()" class="go-back">Go Back</a>
</div>

<!-- MAIN CONTENT -->
<div class="login-container">
  <h2>Payment Successful!</h2>
  <p>Your MonkyBite account has been created.</p>
  <p>You can now log in and start using your cloud.</p>

  <a href="https://cloud.monkybite.com" class="email-login">Go to your Cloud</a>
</div>

<!-- FOOTER -->
<footer>
  <p>© 2025 MonkyBite</p>
</footer>

<script>
  const hamburgerBtn = document.getElementById("hamburger-btn");
  const mobileNav = document.getElementById("mobile-nav");
  hamburgerBtn.addEventListener("click", () => {
    mobileNav.classList.toggle("hidden");
  });
</script>

</body>
</html>

