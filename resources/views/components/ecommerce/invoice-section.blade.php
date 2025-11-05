@props([
    'payment',
    'order',
    'wrapperClass' => '',
    'titleClass' => 'text-sm font-medium text-gray-900 mb-3 flex items-center',
])

@if(!empty($order) && !empty($payment))
    <div class="{{ $wrapperClass }}">
        <h4 class="{{ $titleClass }}">
            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Invoice
        </h4>

        <div class="p-0">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                @if(!empty($payment->invoice_url))
                    <a href="{{ $payment->invoice_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        View Invoice
                    </a>
                @else
                    <button disabled class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-200 rounded-md cursor-not-allowed">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        View Invoice
                    </button>
                @endif

                @if(!empty($payment->invoice_pdf_url) && !empty($order->order_number) && !empty($payment->id))
                    <a href="{{ route('customer.invoice.download', ['orderNumber' => $order->order_number, 'paymentId' => $payment->id]) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Download PDF
                    </a>
                @else
                    <button disabled class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-200 rounded-md cursor-not-allowed">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Download PDF
                    </button>
                @endif
            </div>

            @if(!empty($payment->invoice_number))
                <div class="mt-3 text-sm text-gray-500">
                    Invoice #: {{ $payment->invoice_number }}
                </div>
            @else
                <div class="mt-3 text-sm text-gray-500">
                    Invoice not yet generated
                </div>
            @endif
        </div>
    </div>
@endif