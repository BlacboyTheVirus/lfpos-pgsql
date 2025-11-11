<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoices Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e5e5;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-box {
            flex: 1;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 5px;
            text-align: center;
        }

        .summary-box h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }

        .summary-box p {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }

        th {
            background: #f5f5f5;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #e5e5e5;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-paid { background: #d1fae5; color: #065f46; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-partial { background: #fed7aa; color: #92400e; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoices Report</h1>
        <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
        @if(isset($filters['date_from']) && isset($filters['date_to']))
            <p>Date Range: {{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p>
        @endif
    </div>

    <div class="summary">
        <div class="summary-box">
            <h3>Total Invoices</h3>
            <p>{{ $invoices->count() }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Amount</h3>
            <p>₦{{ number_format($invoices->sum('total'), 2, '.', ',') }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Paid</h3>
            <p>₦{{ number_format($invoices->sum('paid'), 2, '.', ',') }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Due</h3>
            <p>₦{{ number_format($invoices->sum('due'), 2, '.', ',') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="12%">Invoice Code</th>
                <th width="18%">Customer</th>
                <th width="12%">Date</th>
                <th width="13%" class="text-right">Total</th>
                <th width="13%" class="text-right">Paid</th>
                <th width="13%" class="text-right">Due</th>
                <th width="12%">Status</th>
                <th width="12%">Created By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->code }}</td>
                    <td>{{ $invoice->customer?->name ?? 'N/A' }}</td>
                    <td>{{ $invoice->date?->format('d-m-Y') }}</td>
                    <td class="text-right">₦{{ number_format($invoice->total, 0, '.', ',') }}</td>
                    <td class="text-right">₦{{ number_format($invoice->paid, 0, '.', ',') }}</td>
                    <td class="text-right">₦{{ number_format($invoice->due, 0, '.', ',') }}</td>
                    <td>
                        <span class="status-badge status-{{ strtolower($invoice->status?->value ?? 'unpaid') }}">
                            {{ $invoice->status?->getLabel() ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $invoice->createdBy?->name ?? 'System' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($invoices->isNotEmpty())
        <table style="margin-top: -20px;">
            <tbody>
                <tr>
                    <td colspan="3" style="border: none;"></td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px;" class="text-right">
                        <strong>Total:</strong>
                    </td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px; font-weight: bold;" class="text-right">
                        ₦{{ number_format($invoices->sum('total'), 0, '.', ',') }}
                    </td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px; font-weight: bold;" class="text-right">
                        ₦{{ number_format($invoices->sum('paid'), 0, '.', ',') }}
                    </td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px; font-weight: bold;" class="text-right">
                        ₦{{ number_format($invoices->sum('due'), 0, '.', ',') }}
                    </td>
                    <td colspan="2" style="border: none;"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This report was generated by LfPOS Invoice Management System</p>
        <p>Page {{ $currentPage ?? 1 }} of {{ $totalPages ?? 1 }}</p>
    </div>
</body>
</html>
