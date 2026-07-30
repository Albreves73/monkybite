<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payment Successful — MonkyBite</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style.css" />
  <meta http-equiv="refresh" content="3;url=signup.html<?php
    $plan = isset($_GET['plan']) ? '?plan=' . urlencode($_GET['plan']) : '';
    echo $plan;
  ?>">
</head>
<body>

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

<main class="checkout-wrapper">
  <h1 class="checkout-title">Payment Successful!</h1>

  <div class="checkout-box">
    <p>Thank you for your purchase.</p>
    <p>Your subscription is being activated.</p>
    <p>You will be redirected to create your MonkyBite account.</p>

    <a href="signup.html<?php echo $plan ? $plan : ''; ?>" class="plan-button" style="margin-top: 20px;">
      Continue to Sign Up
    </a>
  </div>
</main>

<footer>
  <p>© 2025 MonkyBite.</p>
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
