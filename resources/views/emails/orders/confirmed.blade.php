<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Order Confirmed') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            color: #51545e;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }
        .wrapper {
            background-color: #f4f4f7;
            width: 100%;
            padding: 40px 0;
        }
        .container {
            width: 570px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e8e5ef;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        h1 {
            color: #333333;
            font-size: 22px;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .button-container {
            margin: 30px 0;
            text-align: center;
        }
        .button {
            background-color: #2563eb;
            color: #ffffff;
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e8e5ef;
            padding-top: 20px;
            font-size: 12px;
            color: #b0adc5;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <h1>{{ __('Hello :name,', ['name' => $order->first_name]) }}</h1>
            <p>{{ __('Thank you for your purchase. Your ticket order has been confirmed successfully!') }}</p>
            <p><strong>{{ __('Order Reference:') }}</strong> {{ $order->order_reference }}</p>
            <p><strong>{{ __('Total Paid:') }}</strong> {{ number_format($order->total, 2) }} {{ $order->currency ?? 'USD' }}</p>
            <p>{{ __('Your tickets are attached to this email in PDF format. You will need to show the QR code printed on the tickets at the entrance of the event.') }}</p>
            
            <p>{{ __('If you lose this email, you can always retrieve your tickets from our website at any time.') }}</p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
