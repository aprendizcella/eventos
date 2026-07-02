<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Secure Access Link') }}</title>
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
            text-align: center;
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
            <h1>{{ __('Access Your Tickets') }}</h1>
            <p>{{ __('You requested a secure link to access your tickets. Click the button below to view and download your tickets. This link is only valid for 15 minutes and can only be used once.') }}</p>
            
            <div class="button-container">
                <a href="{{ $url }}" class="button" target="_blank">{{ __('Access My Tickets') }}</a>
            </div>

            <p>{{ __('If you did not request this link, you can safely ignore this email.') }}</p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
