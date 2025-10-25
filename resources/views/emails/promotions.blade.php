<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? 'New Promotion' }}</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* Basic mobile responsiveness */
    @media only screen and (max-width: 600px) {
      .inner-body { width: 100% !important; }
      .content-cell { padding: 20px !important; }
      .btn { width: 100% !important; display: block !important; }
      .stack { display: block !important; width: 100% !important; }
    }
    /* Fallback fonts */
    body, table, td { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
  </style>
</head>
<body style="margin:0;background-color:#f4f6f8;">
  <!-- Preheader (hidden in email clients, used as preview) -->
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $preheader ?? 'You have a new promotion — open to see the details!' }}
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <!-- Email container -->
        <table class="inner-body" width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px;max-width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 6px 20px rgba(16,24,40,0.06);">
          <!-- Header: logo -->
          <tr>
            <td style="padding:20px 28px;background:#ffffff;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                  <span class="text-white text-3xl pl-5">Wagington</span>
                    <!-- Replace with absolute URL to your logo -->
                    {{-- <img src="{{ asset('images/logo.png') }}" alt="Wagington" width="140" style="display:block;border:0;outline:none;text-decoration:none;"> --}}
                  </td>
                  <td align="right" style="vertical-align:middle;color:#6b7280;font-size:14px;">
                    <span style="color:#6b7280;">{{ $now ?? \Carbon\Carbon::now()->format('Y-m-d') }}</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td class="content-cell" style="padding:32px 28px 24px 28px;color:#111827;">
              <h1 style="margin:0 0 8px;font-size:24px;line-height:1.2;color:#111827;">Hello {{ $customer_name ?? 'Customer' }},</h1>

              <p style="margin:0 0 20px;color:#374151;font-size:16px;line-height:1.5;">
                We're excited to let you know about our new promotion:
                <strong style="color:#1A7BE9;">{{ $promotion_name ?? $promo_name ?? 'Special Offer' }}</strong>.
              </p>

              <p style="margin:0 0 18px;color:#374151;font-size:16px;line-height:1.5;">
                You get <strong style="color:#111827;">{{ $discount }}</strong> off your next booking!
                Valid until <strong style="color:#111827;">{{ $valid_till ?? '' }}</strong>.
              </p>

              @if(!empty($promo_code))
              <p style="margin:0 0 22px;">
                <span style="display:inline-block;background:#ebf8ff;color:#1A7BE9;padding:10px 14px;border-radius:8px;font-weight:600;letter-spacing:0.2px;">
                  Promo code: {{ $promo_code }}
                </span>
              </p>
              @endif

              <!-- CTA -->
              <table cellpadding="0" cellspacing="0" role="presentation" style="margin:18px 0 26px;">
                <tr>
                  <td>
                    <a class="btn" href="{{ $cta_url ?? '#' }}" style="background:#1A7BE9`;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:10px;display:inline-block;font-weight:600;">
                      {{ $cta_text ?? 'Book Now' }}
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 18px;color:#6b7280;font-size:14px;line-height:1.6;">
                {{ $body_line ?? "Don't miss out — book soon to claim your discount." }}
              </p>

              <hr style="border:none;border-top:1px solid #eef2f7;margin:24px 0;">

              <p style="margin:0 0 8px;color:#6b7280;font-size:14px;">
                Best regards,
              </p>
              <p style="margin:0;color:#1A7BE9;font-weight:700;">Team Wagington</p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 28px 28px 28px;background:#fafafa;color:#6b7280;font-size:13px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td class="stack" style="vertical-align:top;">
                    <p style="margin:0 0 6px;">If you have any questions, contact us at <a href="mailto:{{ $support_email ?? 'support@example.com' }}" style="color:#6b21a8;text-decoration:none;">{{ $support_email ?? 'support@example.com' }}</a>.</p>
                    <p style="margin:6px 0 0;color:#9ca3af;font-size:12px;">This offer is subject to terms and conditions. See website for details.</p>
                  </td>
                  <td align="right" class="stack" style="vertical-align:middle;">
                    <!-- social icons (replace with your links) -->
                    <a href="{{ $facebook ?? '#' }}" style="text-decoration:none;margin-left:8px;"><img src="{{ $facebook_icon ?? 'https://example.com/icons/facebook.png' }}" alt="Facebook" width="22" style="display:inline-block;border:0;"></a>
                    <a href="{{ $twitter ?? '#' }}" style="text-decoration:none;margin-left:8px;"><img src="{{ $twitter_icon ?? 'https://example.com/icons/twitter.png' }}" alt="Twitter" width="22" style="display:inline-block;border:0;"></a>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" style="padding-top:12px;color:#9ca3af;font-size:12px;">
                    <small>
                      You are receiving this email because you signed up for our service.
                    </small>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>
        <!-- End container -->
      </td>
    </tr>
  </table>
</body>
</html>
