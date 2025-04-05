<!DOCTYPE html>
<html>
<head>
    <title>Bank Balance Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            margin-top: 20px;
        }
        .balance {
            font-size: 24px;
            color: #28a745;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>DTB Bank Balance Notification</h2>
        </div>
        
        <div class="content">
            <p>Dear {{ $customerName }},</p>
            
            <p>Your current bank balance is:</p>
            <p class="balance">KES {{ number_format($balance, 2) }}</p>
            
            <p>This is an automated notification from the DTB Bank Balance checking system.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} DTB Bank. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
