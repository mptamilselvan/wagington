<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $subject ?? 'Your Verification Code' }}</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* Basic mobile responsiveness */
    @media only screen and (max-width: 600px) {
      .inner-body { width: 100% !important; }
      .content-cell { padding: 20px !important; }
      .logo-img { width: 200px !important; }
    }
    /* Fallback fonts */
    body, table, td { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
  </style>
</head>
<body style="margin:0;background-color:#f5f5f5;">
  <!-- Preheader -->
  <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    {{ $preheader ?? 'Your OTP verification code is ready' }}
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <!-- Email container -->
        <table class="inner-body" width="700" cellpadding="0" cellspacing="0" role="presentation" style="width:700px;max-width:100%;background:#ffffff;margin:0 auto;">
          
          <!-- Logo Header -->
          <tr>
            <td align="center" style="padding:60px 40px 40px 40px;">
              <img class="logo-img" src="https://wagington.digitalprizm.net/images/logo.png" alt="The Wagington" width="300" style="display:block;border:0;outline:none;text-decoration:none;max-width:100%;height:auto;">
            </td>
          </tr>

          <!-- Content Box -->
          <tr>
            <td style="padding:0 40px 40px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid #e0e0e0;border-radius:8px;">
                <tr>
                  <td class="content-cell" style="padding:40px;color:#333333;">
                    
                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Dear User,
                    </p>

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Your One-Time Password (OTP) for verification is <strong style="color:#000000;font-size:18px;">{{ $otp }}</strong>. Please use this code within the next <strong> {{ $expiryMinutes ?? 10 }} minutes</strong> to complete your process. If you did not request this, please ignore this message or contact our support team.
                    </p>

                    <p style="margin:20px 0;color:#333333;font-size:16px;line-height:1.6;">
                      If you have any questions or need assistance, feel free to contact our support team.<br>
                      Thank you for choosing The Wagington!
                    </p>

                    <p style="margin:20px 0 8px;color:#333333;font-size:16px;line-height:1.6;">
                      Best regards,
                    </p>
                    <p style="margin:0;color:#333333;font-size:16px;line-height:1.6;">
                      The Wagington Team
                    </p>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:0 40px 40px 40px;">
              
              <!-- Copyright -->
              <p style="margin:0 0 20px;color:#999999;font-size:14px;line-height:1.6;">
                © {{ date('Y') }} The Wagington. All rights reserved.
              </p>

              <!-- Social Icons -->
              <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 30px 0;">
  <tr>
    <td style="padding-right:12px;">
      <a href="{{ $instagram ?? '#' }}" style="text-decoration:none;">
        <div style="width:32px;height:32px;background:linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);border-radius:6px;display:inline-block;text-align:center;line-height:32px;">
          <span style="color:white;font-size:18px;">📷</span>
        </div>
      </a>
    </td>
    <td style="padding-right:12px;">
      <a href="{{ $facebook ?? '#' }}" style="text-decoration:none;">
        <div style="width:32px;height:32px;background:#1877F2;border-radius:6px;display:inline-block;text-align:center;line-height:32px;">
          <span style="color:white;font-size:16px;font-weight:bold;">f</span>
        </div>
      </a>
    </td>
    <td>
      <a href="{{ $twitter ?? '#' }}" style="text-decoration:none;">
        <div style="width:32px;height:32px;background:#000000;border-radius:6px;display:inline-block;text-align:center;line-height:32px;">
          <span style="color:white;font-size:14px;font-weight:bold;">X</span>
        </div>
      </a>
    </td>
  </tr>
</table>
              <!-- Footer Text -->
              <p style="margin:0 0 12px;color:#999999;font-size:13px;line-height:1.6;">
                You are receiving this mail because you registered to join the brandname platform as a user. This also shows that you agree to our Terms of use and Privacy Policies. If you no longer want to receive mails from use, click the unsubscribe link below to unsubscribe.
              </p>

              <!-- Unsubscribe Link -->
              <p style="margin:0;">
                <a href="{{ $unsubscribe_url ?? '#' }}" style="color:#999999;font-size:13px;text-decoration:underline;">Unsubscribe</a>
              </p>

            </td>
          </tr>

        </table>
        <!-- End container -->
      </td>
    </tr>
  </table>
</body>
</html>