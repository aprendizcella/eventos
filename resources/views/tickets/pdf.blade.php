<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Tickets') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333333;
            background: #ffffff;
        }
        .ticket-page {
            page-break-after: always;
            padding: 20px;
            box-sizing: border-box;
            border: 2px dashed #cccccc;
            border-radius: 8px;
            margin-bottom: 30px;
            background: #ffffff;
            overflow: hidden;
        }
        /* Eliminar salto de página para la última página */
        .ticket-page:last-child {
            page-break-after: avoid;
            margin-bottom: 0;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #2563eb;
            text-transform: uppercase;
        }
        .organizer-name {
            font-size: 14px;
            color: #666666;
            margin-top: 5px;
        }
        .content {
            width: 100%;
        }
        .info-col {
            width: 65%;
            float: left;
        }
        .qr-col {
            width: 30%;
            float: right;
            text-align: right;
        }
        .info-group {
            margin-bottom: 15px;
        }
        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #999999;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 15px;
            color: #333333;
            font-weight: bold;
        }
        .clear {
            clear: both;
        }
        .qr-image {
            width: 150px;
            height: 150px;
            border: 1px solid #eeeeee;
            padding: 5px;
            background: #ffffff;
            border-radius: 4px;
        }
        .unique-code-display {
            font-family: monospace;
            font-size: 13px;
            margin-top: 8px;
            font-weight: bold;
            color: #555555;
            text-align: center;
            width: 150px;
            float: right;
        }
        .footer-note {
            margin-top: 30px;
            border-top: 1px solid #eeeeee;
            padding-top: 10px;
            font-size: 11px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>
    @foreach ($attendees as $attendee)
        <div class="ticket-page">
            <div class="header">
                <h1>{{ $attendee->ticketOrder->event->title }}</h1>
                <div class="organizer-name">
                    {{ __('Organized by:') }} {{ $attendee->ticketOrder->event->organizer->name }}
                </div>
            </div>
            
            <div class="content">
                <div class="info-col">
                    <div class="info-group">
                        <div class="info-label">{{ __('Attendee') }}</div>
                        <div class="info-value">{{ $attendee->first_name }} {{ $attendee->last_name }}</div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">{{ __('Email') }}</div>
                        <div class="info-value">{{ $attendee->email }}</div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">{{ __('Date & Time') }}</div>
                        <div class="info-value">
                            @if ($attendee->ticketOrder->event->starts_at)
                                {{ $attendee->ticketOrder->event->starts_at->format('F d, Y - H:i') }}
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">{{ __('Venue') }}</div>
                        <div class="info-value">
                            @if ($attendee->ticketOrder->event->venue)
                                {{ $attendee->ticketOrder->event->venue->name }}<br>
                                <span style="font-size: 13px; font-weight: normal; color: #666666;">
                                    {{ $attendee->ticketOrder->event->venue->address }}, {{ $attendee->ticketOrder->event->venue->city }}
                                </span>
                            @else
                                {{ __('Online / Virtual Event') }}
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="qr-col">
                    <img src="{{ $qrCodes[$attendee->attendee_id] }}" class="qr-image" alt="QR Code">
                    <div class="clear"></div>
                    <div class="unique-code-display">{{ $attendee->unique_code }}</div>
                </div>
                
                <div class="clear"></div>
            </div>
            
            <div class="footer-note">
                {{ __('Please present this QR code at the entrance of the event. Photocopies or digital versions on your mobile phone are accepted.') }}
            </div>
        </div>
    @endforeach
</body>
</html>
