<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - KosmoHealth</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #6366f1;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .footer {
            background-color: #374151;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 12px;
        }
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 5px;
        }
        .field-value {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        .message-content {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #6366f1;
            margin-top: 10px;
        }
        .metadata {
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            color: #6b7280;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Contact Form Submission</h1>
        <p>KosmoHealth Support Team</p>
    </div>

    <div class="content">
        <p>You have received a new message through the KosmoHealth contact form.</p>

        <div class="field">
            <div class="field-label">Name:</div>
            <div class="field-value">{{ $name }}</div>
        </div>

        <div class="field">
            <div class="field-label">Email:</div>
            <div class="field-value">{{ $email }}</div>
        </div>

        @if($phone)
        <div class="field">
            <div class="field-label">Phone:</div>
            <div class="field-value">{{ $phone }}</div>
        </div>
        @endif

        <div class="field">
            <div class="field-label">Subject:</div>
            <div class="field-value">{{ $subject }}</div>
        </div>

        <div class="field">
            <div class="field-label">Message:</div>
            <div class="message-content">
                {!! nl2br(e($message)) !!}
            </div>
        </div>

        <div class="metadata">
            <strong>Submission Details:</strong><br>
            Message ID: {{ $message_id }}<br>
            Submitted: {{ $submitted_at }}<br>
            Reply to: {{ $email }}
        </div>
    </div>

    <div class="footer">
        <p>This email was automatically generated from the KosmoHealth contact form.</p>
        <p>Please reply directly to this email to respond to the sender.</p>
    </div>
</body>
</html>
