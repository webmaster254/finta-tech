<!DOCTYPE html>
<html>
<head>
    <title>Payment Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #2d3748;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .payment-details {
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .amount {
            font-size: 28px;
            color: #38a169;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-label {
            color: #4a5568;
            font-weight: 600;
        }
        .detail-value {
            color: #2d3748;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
        .thank-you {
            text-align: center;
            margin: 20px 0;
            color: #4a5568;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Notification</h1>
        </div>

        <div class="content">


            <p>{{ $transaction['narration'] }}.</p>

            <div class="payment-details">
                <div class="customer-name">
                    <strong>Customer Name:</strong> {{ $transaction['customer_name'] }}
                </div>

                <div class="detail-row">
                    <span class="detail-label">Customer Number:</span>
                    <span class="detail-value">{{ $transaction['customer_mobile'] }}</span>
                </div>

                <div class="amount">
                    KES {{ number_format($transaction['amount'], 2) }}
                </div>

                <div class="detail-row">
                    <span class="detail-label">Transaction Code:</span>
                    <span class="detail-value">{{ $transaction['transaction_code'] }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ $transaction['value_date'] }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Available Balance:</span>
                    <span class="detail-value">KES {{ $transaction['available_balance'] }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value">{{ $transaction['transaction_description'] }}</span>
                </div>
            </div>

            <div class="thank-you">
                <p>Thank you </p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from DTB Bank. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} DTB Bank. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
