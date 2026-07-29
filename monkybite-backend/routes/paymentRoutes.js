const express = require("express");
const router = express.Router();
const { Client, Environment } = require("square");
const crypto = require("crypto");

const client = new Client({
  accessToken: process.env.SQUARE_ACCESS_TOKEN,
  environment: Environment.Production
});

const plans = {
  starter: { amount: 1000 },
  pro: { amount: 2000 },
  enterprise: { amount: 5000 }
};

router.post("/process-payment", async (req, res) => {
  const { token, plan } = req.body;

  if (!plans[plan]) {
    return res.json({ success: false, message: "Invalid plan" });
  }

  try {
    await client.paymentsApi.createPayment({
      sourceId: token,
      idempotencyKey: crypto.randomUUID(),
      amountMoney: {
        amount: plans[plan].amount,
        currency: "USD"
      },
      note: plan
    });

    res.json({
      success: true,
      redirect: `/payment-success.php?plan=${plan}`
    });

  } catch (error) {
    console.error(error);
    res.json({ success: false, message: "Square payment error" });
  }
});

module.exports = router;

