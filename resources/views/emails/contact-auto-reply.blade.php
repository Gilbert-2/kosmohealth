<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you for contacting KosmoHealth</title>
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
        .contact-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #10b981;
        }
        .contact-item {
            margin-bottom: 10px;
        }
        .contact-label {
            font-weight: bold;
            color: #374151;
        }
        .highlight {
            background-color: #dbeafe;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thank You!</h1>
        <p>Your message has been received</p>
    </div>

    <div class="content">
        <p>Dear {{ $name }},</p>

        <p>Thank you for contacting KosmoHealth. We have successfully received your message and our support team will review it shortly.</p>

        <div class="highlight">
            <strong>What happens next?</strong><br>
            • Our team typically responds within 24-48 hours<br>
            • You will receive a response at the email address you provided<br>
            • For urgent matters, please call us directly
        </div>

        <p><strong>Your message reference ID:</strong> {{ $message_id }}</p>
        <p><strong>Submitted on:</strong> {{ $submitted_at }}</p>

        @if($contact_info)
        <div class="contact-info">
            <h3>Contact Information</h3>
            
            @if($contact_info['email'])
            <div class="contact-item">
                <span class="contact-label">Email:</span> {{ $contact_info['email'] }}
            </div>
            @endif

            @if($contact_info['phone'])
            <div class="contact-item">
                <span class="contact-label">Phone:</span> {{ $contact_info['phone'] }}
            </div>
            @endif

            @if($contact_info['address'])
            <div class="contact-item">
                <span class="contact-label">Address:</span> {{ $contact_info['address'] }}
            </div>
            @endif

            @if($contact_info['businessHours'])
            <div class="contact-item">
                <span class="contact-label">Business Hours:</span> {{ $contact_info['businessHours'] }}
            </div>
            @endif
        </div>
        @endif

        <p>In the meantime, feel free to explore our platform and discover how KosmoHealth can help you take control of your reproductive health journey.</p>

        <p>Best regards,<br>
        The KosmoHealth Support Team</p>
    </div>

    <div class="footer">
        <p>This is an automated response. Please do not reply to this email.</p>
        <p>© {{ date('Y') }} KosmoHealth. All rights reserved.</p>
    </div>
</body>
</html>
