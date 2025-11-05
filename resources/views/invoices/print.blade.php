<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->code }} - Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .invoice-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #2d5016, #4a7c22);
            color: white;
            padding: 30px;
            position: relative;
        }

        .company-logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: #4a7c22;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            font-size: 18px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
        }

        .invoice-title {
            position: absolute;
            top: 30px;
            right: 30px;
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 36px;
            font-weight: 300;
            margin-bottom: 0;
        }

        .invoice-date {
            font-size: 16px;
            opacity: 0.9;
        }

        .invoice-body {
            padding: 40px;
        }

        .invoice-number {
            color: #4a7c22;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .detail-section h3 {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border-bottom: 2px solid #4a7c22;
            padding-bottom: 5px;
        }

        .company-details, .customer-details {
            line-height: 1.8;
            font-size: 14px;
        }

        .company-details strong, .customer-details strong {
            font-size: 15px;
            display: block;
            margin-bottom: 5px;
        }

        .invoice-summary {
            text-align: left;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-item.highlight {
            font-weight: 700;
            font-size: 18px;
            border-top: 2px solid #4a7c22;
            padding-top: 15px;
            margin-top: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .status-partial { background: #fff3cd; color: #856404; }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 40px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .products-table thead {
            background: #f8f9fa;
        }

        .products-table th,
        .products-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .products-table th {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .products-table td {
            font-size: 14px;
            color: #495057;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        .payment-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }

        .payment-details h3,
        .bank-details h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4a7c22;
        }

        .bank-details {
            margin-top: 40px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .payment-table th,
        .payment-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        .payment-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .payment-total {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            padding-top: 15px;
            border-top: 2px solid #4a7c22;
        }

        .bank-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4a7c22;
        }

        .bank-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .bank-row:last-child {
            margin-bottom: 0;
        }

        .bank-label {
            font-weight: 600;
            color: #666;
        }

        .amount {
            font-weight: 600;
            color: #333;
        }

        .amount-due {
            font-weight: 700;
            font-size: 16px;

        }

        .totals-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .amount-in-words {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            font-style: italic;
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        .note-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #4a7c22;
            border-radius: 4px;
        }

        .note-section h4 {
            margin-bottom: 10px;
            color: #4a7c22;
            font-size: 16px;
            font-weight: 600;
        }

        .note-section p {
            color: #666;
            font-style: italic;
            line-height: 1.6;
            font-size: 14px;
        }

        .thank-you {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #666;
            font-size: 14px;
        }

        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
        }

        @media (max-width: 768px) {
            .invoice-details {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .payment-section {
                grid-template-columns: 1fr;
            }
            .invoice-title {
                position: static;
                text-align: left;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-logo">
                <div class="logo-icon">BK</div>
                <div class="company-name">{{ $settings['company_name'] }}</div>
            </div>

            <div class="invoice-title">
                <h1>Invoice</h1>
                <div class="invoice-date">Date: {{ $invoice->date->format('jS, F Y') }}</div>
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- Invoice Number -->
            <div class="invoice-number">
                # {{ $invoice->code }}
            </div>

            <!-- Invoice Details -->
            <div class="invoice-details">
                <!-- From Section -->
                <div class="detail-section">
                    <h3>From</h3>
                    <div class="company-details">
                        <strong>{{ $settings['company_name'] }}</strong><br>
                        4/6 Cameroon Road by Gyari Road,<br>
                        Kaduna - Nigeria<br>
                        Phone: +234 803 5988 543<br>
                        Email: info@blacboykreative.com
                    </div>
                </div>

                <!-- To Section -->
                <div class="detail-section">
                    <h3>To</h3>
                    <div class="customer-details">
                        <strong>{{ $invoice->customer->name }}</strong><br>
                        @if($invoice->customer->phone)
                            Phone: {{ $invoice->customer->phone }}<br>
                        @endif
                        @if($invoice->customer->email)
                            Email: {{ $invoice->customer->email }}<br>
                        @endif
                    </div>
                </div>

                <!-- Invoice Summary -->
                <div class="detail-section">
                    <h3>Invoice Summary</h3>
                    <div class="invoice-summary">
                        <div class="summary-item">
                            <span>Invoice Total:</span>
                            <span class="amount">₦ {{ number_format($invoice->total) }}</span>
                        </div>
                        <div class="summary-item">
                            <span>Amount Paid:</span>
                            <span class="amount">₦ {{ number_format($invoice->paid) }}</span>
                        </div>
                        <div class="summary-item">
                            <span>Amount Due:</span>
                            <span class="amount amount-due">₦ {{ number_format($invoice->due) }}</span>
                        </div>
                        <div class="summary-item" style="margin-top: 15px; align-items: center;">
                            <span>Payment Status:</span>
                            <span class="status-badge status-{{ strtolower($invoice->status->name) }}">
                                {{ $invoice->status->name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Table -->
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th class="text-center">Size (w x h) ft</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Unit Amount</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->products as $product)
                    <tr>
                        <td>{{ $product->product->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $product->width }} x {{ $product->height }}</td>
                        <td class="text-right">{{ number_format($product->unit_price) }}</td>
                        <td class="text-right">{{ number_format($product->width * $product->height * $product->unit_price) }}</td>
                        <td class="text-center">{{ $product->quantity }}</td>
                        <td class="text-right amount">{{ number_format($product->product_amount) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Payment and Bank Details Section -->
            <div class="payment-section">
                <!-- Payment Details -->
                <div class="payment-details">
                    <h3>Payment Details</h3>
                    @if($invoice->payments->count() > 0)
                        <table class="payment-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $index => $payment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                                    <td>{{ $payment->payment_type->getLabel() }}</td>
                                    <td>{{ $payment->note ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($payment->amount) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="payment-total">
                            <strong>Total: ₦ {{ number_format($invoice->payments->sum('amount')) }}</strong>
                        </div>
                    @else
                        <p style="color: #666; font-style: italic;">No payments recorded yet.</p>
                    @endif

                    <!-- Bank Account Details -->
                    <div class="bank-details">
                        <h3><i style="margin-right: 8px;">🏦</i> Bank Account Details</h3>
                        <div class="bank-info">
                            <div class="bank-row">
                                <span class="bank-label">Account Name</span>
                                <span>{{ $settings['bank_account_name'] }}</span>
                            </div>
                            <div class="bank-row">
                                <span class="bank-label">Account Number</span>
                                <span>{{ $settings['bank_account_number'] }}</span>
                            </div>
                            <div class="bank-row">
                                <span class="bank-label">Bank</span>
                                <span>{{ $settings['bank_name'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div>
                    <div class="totals-box">
                        <div class="summary-item">
                            <span>Subtotal:</span>
                            <span class="amount">₦ {{ number_format($invoice->subtotal) }}</span>
                        </div>
                        <div class="summary-item">
                            <span>Less Discount:</span>
                            <span class="amount">{{ number_format($invoice->discount) }}</span>
                        </div>
                        <div class="summary-item">
                            <span>Roundoff:</span>
                            <span class="amount">{{ number_format($invoice->round_off) }}</span>
                        </div>
                        <div class="summary-item highlight">
                            <span>Total:</span>
                            <span class="amount" style="color: #4a7c22;">₦ {{ number_format($invoice->total) }}</span>
                        </div>
                        <div class="amount-in-words">
                            {{ $invoice->getTotalInWords() }}
                        </div>
                    </div>

                    @if($invoice->note)
                    <div class="note-section">
                        <h4>Note:</h4>
                        <p>{{ $invoice->note }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="thank-you">
                <p>Thank you for your business!</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
