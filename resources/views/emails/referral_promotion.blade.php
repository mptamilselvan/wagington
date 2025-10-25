<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Referral Promotion</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8f9fa; padding:20px;">
    <div style="max-width:600px; margin:auto; background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
        <div style="text-align:center; margin-bottom:20px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width:150px;">
        </div>

        <h2 style="color:#2d3748;">Hello {{ $customer_name ?? 'Customer' }},</h2>
        <p>We’re excited to announce a new Referral Promotion</p>

        <p style="font-size:14px;"><strong>{{ $promotion_name }}</strong></p>
        <span>{{ $description }}</span>

        <p>
            👉 Referrer Reward: <b>{{ $discount_type == 'percentage'? $referrer_reward.'%':$referrer_reward.'SGD' }}</b><br>
            👉 Referee Reward: <b>{{ $discount_type == 'percentage'? $referee_reward.'%':$referee_reward.'SGD' }}</b>
        </p>

        <p>
            Valid from <b>{{ $valid_from_date }}</b> 
            till <b>{{ $valid_till_date }}</b>.
        </p>

        {{-- <p style="margin-top:20px;">
            <a href="{{ url('/') }}" style="background:#1d72b8; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;">Check Promotion</a>
        </p> --}}

        <p style="color:#718096; font-size:14px; margin-top:20px;">
            Thank you for being a valued customer.
        </p>
        <br>
        <p style="margin:0 0 8px;color:#6b7280;font-size:14px;">
            Best regards,<br>
            Team Wagington
        </p>
    </div>
</body>
</html>
