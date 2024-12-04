<?php 

$emailBody = '

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
    }
    .container {
      max-width: 600px;
      margin: 50px auto;
      background: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      text-align: center;
    }
    .header {
      font-size: 24px;
      font-weight: bold;
      color: #333333;
    }
    .message {
      margin: 20px 0;
      font-size: 16px;
      color: #555555;
    }
    .button {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 24px;
      background-color: #007BFF;
      color: #ffffff;
      text-decoration: none;
      font-size: 16px;
      border-radius: 4px;
      transition: background-color 0.3s ease;
    }
    .button:hover {
      background-color: #0056b3;
    }
    .footer {
      margin-top: 20px;
      font-size: 12px;
      color: #999999;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">Verify Your Email</div>
    <div class="message">
      Thank you for signing up! Please confirm your email address by clicking the button below:
    </div>
    <a href="https://your-verification-link.com" class="button">Verify Email</a>
    <div class="footer">
      If you didn\'t request this email, you can safely ignore it.
    </div>
  </div>
</body>
</html>
';
