<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Payment Failed</title>
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
    Action Required: Payment Failed for Order #{{ $order->order_number }}
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
                      Action Required: Payment Failed for Your Order Attempt
                    </p>

                    <div style="background:#fff5f5;border-left:4px solid #e53e3e;border-radius:4px;padding:15px;margin:20px 0;">
                      <p style="margin:0 0 10px 0;color:#c53030;font-size:16px;line-height:1.5;">
                        <strong>We had an issue processing your recent payment for ${{ number_format($order->total_amount, 2) }}.</strong>
                      </p>
                      <p style="margin:0;color:#4a5568;font-size:15px;line-height:1.5;">
                        Your order has not been placed. Because the payment failed, the items in your cart have NOT been secured or reserved.
                      </p>
                    </div>

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      <strong>Don't miss out!</strong> Please update your payment details immediately to complete your purchase before these items sell out.
                    </p>

                    <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:25px 0;">
                      <h3 style="margin:0 0 15px 0;color:#2d3748;font-size:18px;">Order Reference</h3>
                      <p style="margin:0 0 5px 0;color:#333333;font-size:14px;line-height:1.5;">
                        <strong>Order Attempt Ref:</strong> #{{ $order->order_number }}
                      </p>
                      <p style="margin:10px 0 0 0;color:#4a5568;font-size:14px;line-height:1.5;">
                        If you are unsure why this happened, please try using a different payment method or contact your card issuer first.
                      </p>
                    </div>

                    <p style="margin:20px 0;color:#333333;font-size:16px;line-height:1.6;">
                      Need Assistance? Our support team is here to help. Reply to this email or call us at +65 1234 5678.
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
                You are receiving this email because a payment attempt was made on our website.
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