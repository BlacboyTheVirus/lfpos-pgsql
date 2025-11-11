<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customers Report</title>
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
        <h1>Customers Report</h1>
        <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
        @if(isset($filters['created_from']) && isset($filters['created_to']))
            <p>Date Range: {{ $filters['created_from'] }} to {{ $filters['created_to'] }}</p>
        @endif
    </div>

    <div class="summary">
        <div class="summary-box">
            <h3>Total Customers</h3>
            <p>{{ $customers->count() }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Invoices</h3>
            <p>{{ $customers->sum('invoices_count') }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Invoice Amount</h3>
            <p>₦{{ number_format($customers->sum('invoices_sum_total') / 100, 2, '.', ',') }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Due</h3>
            <p>₦{{ number_format($customers->sum('invoices_sum_due') / 100, 2, '.', ',') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Code</th>
                <th width="18%">Name</th>
                <th width="12%">Phone</th>
                <th width="14%">Email</th>
                <th width="8%" class="text-center">Invoices</th>
                <th width="14%" class="text-right">Invoice Amount</th>
                <th width="14%" class="text-right">Total Due</th>
                <th width="10%">Created By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
                <tr>
                    <td>{{ $customer->code }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone ?? 'N/A' }}</td>
                    <td style="font-size: 10px;">{{ $customer->email ?? 'N/A' }}</td>
                    <td class="text-center">{{ $customer->invoices_count ?? 0 }}</td>
                    <td class="text-right">₦{{ number_format(($customer->invoices_sum_total ?? 0) / 100, 0, '.', ',') }}</td>
                    <td class="text-right">₦{{ number_format(($customer->invoices_sum_due ?? 0) / 100, 0, '.', ',') }}</td>
                    <td>{{ $customer->createdBy?->name ?? 'System' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($customers->isNotEmpty())
        <table style="margin-top: -20px;">
            <tbody>
                <tr>
                    <td colspan="4" style="border: none;"></td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px;" class="text-center">
                        <strong>{{ $customers->sum('invoices_count') }}</strong>
                    </td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px; font-weight: bold;" class="text-right">
                        ₦{{ number_format($customers->sum('invoices_sum_total') / 100, 0, '.', ',') }}
                    </td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px; font-weight: bold;" class="text-right">
                        ₦{{ number_format($customers->sum('invoices_sum_due') / 100, 0, '.', ',') }}
                    </td>
                    <td style="border: none;"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This report was generated by LfPOS Customer Management System</p>
        <p>Page {{ $currentPage ?? 1 }} of {{ $totalPages ?? 1 }}</p>
    </div>
</body>
</html>
