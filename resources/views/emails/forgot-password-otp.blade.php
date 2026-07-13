<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #2BB5BD, #1A7A6F);
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 0;
            font-weight: 700;
        }
        .body {
            padding: 32px 24px;
            color: #333333;
        }
        .otp-code {
            text-align: center;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #1A7A6F;
            margin: 24px 0;
            padding: 16px;
            background: #f0f9f8;
            border-radius: 12px;
        }
        .footer {
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #888888;
            border-top: 1px solid #eeeeee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset</h1>
        </div>
        <div class="body">
            <p>Hello, <strong>{{ $name }}</strong>!</p>
            <p>We received a request to reset your TanodTractor account password. Use the OTP code below to proceed:</p>

            <div class="otp-code">{{ $otp }}</div>

            <p>This code is valid for <strong>10 minutes</strong>. If you did not request a password reset, please ignore this email.</p>

            <p style="margin-top: 24px;">Salamat,<br><strong>TanodTractor Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} TanodTractor. All rights reserved.
        </div>
    </div>
</body>
</html>
