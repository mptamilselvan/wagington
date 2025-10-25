<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Referral Promotion</title>
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
    A new user signed up with your referral code!
  </div>

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <!-- Email container -->
        <table class="inner-body" width="700" cellpadding="0" cellspacing="0" role="presentation" style="width:700px;max-width:100%;background:#ffffff;margin:0 auto;">
          
          <!-- Logo Header -->
          <tr>
            <td align="center" style="padding:40px 40px 20px 40px;">
              <img class="logo-img" src="https://wagington.digitalprizm.net/images/logo.png" alt="The Wagington" width="200" style="display:block;border:0;outline:none;text-decoration:none;max-width:100%;height:auto;">
            </td>
          </tr>

          <!-- Content Box -->
          <tr>
            <td style="padding:0 40px 40px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid #e0e0e0;border-radius:8px;">
                <tr>
                  <td class="content-cell" style="padding:40px;color:#333333;">
                    
                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      <strong>Hello {{ $user->name ?? 'Customer' }},</strong>
                    </p>

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Exciting update! You've just successfully introduced another user to Wagington! ✨
                    </p>

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      With our app, you can easily track the list of referees who joined through your link and see how many days remain for them to redeem their promo code.
                    </p>

                    <p style="margin:20px 0;color:#333333;font-size:16px;line-height:1.6;">
                      Keep the momentum going— you're fueling our community growth!
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

              <!-- Footer Text -->
              <p style="margin:0 0 12px;color:#999999;font-size:13px;line-height:1.6;">
                You are receiving this email because you participated in our referral program.
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