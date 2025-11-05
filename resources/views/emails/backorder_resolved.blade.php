<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Backorder Resolved</title>
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
    Good news! Your items for order #{{ $order->order_number }} are now in stock and ready to ship
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
                      <strong>Hi {{ $user->name ?? 'Customer' }},</strong>
                    </p>

                    <h2 style="margin:0 0 20px;color:#10b981;font-size:24px;line-height:1.3;">
                      🎉 Great News! Your Items Are Now in Stock
                    </h2>

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      We're excited to let you know that all items in your order <strong>#{{ $order->order_number }}</strong> are now available and ready to ship!
                    </p>

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Your order is now being prepared for shipment. We'll send you tracking details via email as soon as your items are on their way.
                    </p>

                    <!-- Status Badge -->
                    <div style="background:#d1fae5;border-left:4px solid #10b981;border-radius:4px;padding:16px;margin:20px 0;">
                      <p style="margin:0;color:#065f46;font-size:14px;line-height:1.5;">
                        <strong>✅ Status Update:</strong> Out of Stock → Ready to Ship
                      </p>
                    </div>

                    <!-- Order Summary -->
                    <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                      <h3 style="margin:0 0 15px 0;color:#2d3748;font-size:18px;">Order Details</h3>
                      
                      <div style="margin-bottom:15px;">
                        <p style="margin:0 0 8px 0;color:#333333;font-size:14px;line-height:1.5;">
                          <strong>Order Number:</strong> #{{ $order->order_number }}
                        </p>
                        <p style="margin:0 0 8px 0;color:#333333;font-size:14px;line-height:1.5;">
                          <strong>Order Date:</strong> {{ $order->created_at->format('j F Y') }}
                        </p>
                        <p style="margin:0;color:#333333;font-size:14px;line-height:1.5;">
                          <strong>Total Amount:</strong> ${{ number_format($order->total_amount, 2) }}
                        </p>
                      </div>

                      <!-- Items that were backordered -->
                      <div style="margin:15px 0;">
                        <h4 style="margin:0 0 10px 0;color:#4a5568;font-size:16px;">Items Now Available:</h4>
                        @foreach($order->items as $item)
                          <p style="margin:0 0 8px 0;color:#4a5568;font-size:14px;line-height:1.4;">
                            • {{ $item->product_name }} 
                            @if($item->variant_display_name)
                              ({{ $item->variant_display_name }})
                            @endif
                            - Quantity: {{ $item->quantity }}
                          </p>
                        @endforeach
                      </div>
                    </div>

                    <!-- CTA Button -->
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:30px 0;">
                      <tr>
                        <td align="center">
                          <a href="{{ $orderUrl }}" style="display:inline-block;padding:14px 28px;background-color:#3b82f6;color:#ffffff;text-decoration:none;border-radius:6px;font-size:16px;font-weight:600;">
                            View Order Details
                          </a>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:20px 0 0 0;color:#666666;font-size:14px;line-height:1.6;">
                      Thank you for your patience! If you have any questions, please don't hesitate to contact our support team.
                    </p>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:0 40px 40px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                  <td align="center" style="padding:20px 0;border-top:1px solid #e0e0e0;">
                    <p style="margin:0 0 8px 0;color:#9ca3af;font-size:14px;line-height:1.4;">
                      This is an automated notification from The Wagington
                    </p>
                    <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.4;">
                      &copy; {{ date('Y') }} The Wagington. All rights reserved.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
