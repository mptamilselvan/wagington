<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Order Confirmation</title>
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
    Your order #{{ $order->order_number }} has been confirmed
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

                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Success! Your Order <strong>#{{ $order->order_number }}</strong> is Confirmed!
                    </p>

                    @if($order->shipping_address_id && $order->shipping_amount > 0 && $order->shippingAddress)
                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Thank you for your purchase. Your order is being prepared for shipment. We'll send a separate email with tracking information shortly.
                    </p>
                    @else
                    <p style="margin:0 0 20px;color:#333333;font-size:16px;line-height:1.6;">
                      Thank you for your purchase. Your order is being processed.
                    </p>
                    @endif

          <!-- Order Summary -->
          @php
            // Ensure $taxRate is always defined. Preference order:
            // 1) passed-in $taxRate
            // 2) stored applied_tax_rate on the order
            // 3) derive from tax_amount/subtotal when safe
            // 4) fallback to a site default config or 0
            if (!isset($taxRate)) {
              if (!empty($order->applied_tax_rate)) {
                $taxRate = (float) $order->applied_tax_rate;
              } elseif (!empty($order->subtotal) && (float)$order->subtotal > 0 && isset($order->tax_amount)) {
                $taxRate = ($order->tax_amount / $order->subtotal) * 100;
              } else {
                $taxRate = (float) config('app.default_tax_rate', 0.0);
              }
            }
            // Clamp to non-negative and reasonable precision
            $taxRate = max(0.0, round((float)$taxRate, 2));
          @endphp
                    <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                      <h3 style="margin:0 0 15px 0;color:#2d3748;font-size:18px;">Order Summary</h3>
                      
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

                      <!-- Items Preview -->
                      <div style="margin:15px 0;">
                        <h4 style="margin:0 0 10px 0;color:#4a5568;font-size:16px;">Items:</h4>
                        @foreach($order->items->take(3) as $item)
                          <p style="margin:0 0 5px 0;color:#4a5568;font-size:14px;line-height:1.4;">
                            {{ $item->product_name }} @if($item->variant_display_name) ({{ $item->variant_display_name }}) @endif × {{ $item->quantity }}
                          </p>
                        @endforeach
                        @if($order->items->count() > 3)
                          <p style="margin:5px 0 0 0;color:#6b7280;font-size:13px;">
                            +{{ $order->items->count() - 3 }} more items
                          </p>
                        @endif
                      </div>

                      <!-- Price Breakdown -->
                      <div style="margin:15px 0 0 0;">
                        <h4 style="margin:0 0 10px 0;color:#4a5568;font-size:16px;">Price Breakdown:</h4>
                        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                          <tr>
                            <td style="padding:3px 0;color:#4a5568;">Subtotal:</td>
                            <td style="padding:3px 0;text-align:right;color:#4a5568;">${{ number_format($order->subtotal, 2) }}</td>
                          </tr>
                          @if($order->coupon_discount_amount > 0)
                            <tr>
                              <td style="padding:3px 0;color:#38a169;">Discounts:</td>
                              <td style="padding:3px 0;text-align:right;color:#38a169;">-${{ number_format($order->coupon_discount_amount, 2) }}</td>
                            </tr>
                          @endif
                          @if($order->shipping_amount > 0)
                            <tr>
                              <td style="padding:3px 0;color:#4a5568;">Shipping:</td>
                              <td style="padding:3px 0;text-align:right;color:#4a5568;">${{ number_format($order->shipping_amount, 2) }}</td>
                            </tr>
                          @endif
                          @if($order->tax_amount > 0)
                            <tr>
                              <td style="padding:3px 0;color:#4a5568;">GST ({{ number_format($taxRate, 2) }}%):</td>
                              <td style="padding:3px 0;text-align:right;color:#4a5568;">${{ number_format($order->tax_amount, 2) }}</td>
                            </tr>
                          @endif
                          <tr style="border-top:1px solid #e2e8f0;">
                            <td style="padding:8px 0 0 0;color:#2d3748;font-weight:bold;">TOTAL PAID:</td>
                            <td style="padding:8px 0 0 0;text-align:right;color:#2d3748;font-weight:bold;">${{ number_format($order->total_amount, 2) }}</td>
                          </tr>
                        </table>
                      </div>
                    </div>

                    <!-- Payment & Invoice -->
                    @php
                      $payment = $order->payments->sortByDesc('id')->first();
                    @endphp
                    @if($payment)
                      <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                        <h3 style="margin:0 0 15px 0;color:#2d3748;font-size:18px;">Payment Information</h3>
                        
                        @if($payment->payment_gateway)
                          <p style="margin:0 0 10px 0;color:#333333;font-size:14px;line-height:1.5;">
                            <strong>Payment Method:</strong> 
                            @if($payment->card_last4)
                              {{ ucfirst($payment->payment_gateway) }} ending {{ $payment->card_last4 }}
                            @else
                              {{ ucfirst($payment->payment_gateway) }}
                            @endif
                          </p>
                        @endif
                        
                        @if($payment->invoice_pdf_url)
                          <p style="margin:0 0 5px 0;color:#333333;font-size:14px;line-height:1.5;">
                            <strong>Invoice:</strong> 
                            <a href="{{ route('customer.invoice.download', ['orderNumber' => $order->order_number, 'paymentId' => $payment->id]) }}" style="color:#3182ce;">Download Invoice (PDF)</a>
                          </p>
                        @endif
                        
                        <div style="text-align:center;margin:15px 0 0 0;">
                          <a href="{{ route('customer.order-detail', $order->order_number) }}" 
                             style="display:inline-block;padding:12px 24px;background:#3182ce;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:14px;">
                            View Order Details
                          </a>
                        </div>
                      </div>
                    @endif

                    <!-- Shipping Address -->
                    @if($order->shipping_address_id && $order->shipping_amount > 0 && $order->shippingAddress)
                      <div style="background:#f8f9fa;border-radius:8px;padding:20px;margin:20px 0;">
                        <h3 style="margin:0 0 15px 0;color:#2d3748;font-size:18px;">Shipping Address</h3>
                        <p style="margin:0;color:#333333;font-size:14px;line-height:1.5;">
                          <strong>{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</strong><br>
                          {{ $order->shippingAddress->address_line1 }}<br>
                          @if($order->shippingAddress->address_line2)
                            {{ $order->shippingAddress->address_line2 }}<br>
                          @endif
                          @php
                            $shipState = $order->shippingAddress->state ?? $order->shippingAddress->region ?? null;
                          @endphp
                          {{ $order->shippingAddress->city }}@if(!empty($shipState)), {{ $shipState }}@endif, {{ $order->shippingAddress->postal_code }}<br>
                          {{ $order->shippingAddress->country }}
                          @if($order->shippingAddress->phone)
                            <br>Phone: {{ $order->shippingAddress->phone }}
                          @endif
                        </p>
                      </div>
                    @endif

                    <p style="margin:20px 0;color:#333333;font-size:16px;line-height:1.6;">
                      Need help? Reply to this email or contact our support team.<br>
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

              <!-- Footer Text -->
              <p style="margin:0 0 12px;color:#999999;font-size:13px;line-height:1.6;">
                You are receiving this email because you placed an order on our website. This email serves as your official receipt.
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