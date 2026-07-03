<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Waitlist Ticket Available') }}</title>
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
            background-color: #10b981;
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
            <h1>{{ __('A Ticket is Available for You!') }}</h1>
            <p>{{ __('Good news! A spot has opened up on the waitlist for the event: :title.', ['title' => $eventTitle]) }}</p>
            <p>{{ __('Click the button below to complete your registration. This invitation is valid for 24 hours. If you do not register within this timeframe, your spot will be given to the next person in line.') }}</p>
            
            <div class="button-container">
                <a href="{{ $url }}" class="button" target="_blank">{{ __('Complete Registration') }}</a>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
