<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expenses Report</title>
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

        .category-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }

        .category-miscellaneous { background: #f3f4f6; color: #374151; }
        .category-materials { background: #d1fae5; color: #065f46; }
        .category-utilities { background: #fed7aa; color: #92400e; }
        .category-repairs_and_cleaning { background: #fee2e2; color: #991b1b; }
        .category-staff { background: #dbeafe; color: #1e40af; }

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
        <h1>Expenses Report</h1>
        <p>Generated on: {{ now()->format('F d, Y h:i A') }}</p>
        @if(isset($filters['date_from']) && isset($filters['date_to']))
            <p>Date Range: {{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p>
        @endif
    </div>

    <div class="summary">
        <div class="summary-box">
            <h3>Total Records</h3>
            <p>{{ $expenses->count() }}</p>
        </div>
        <div class="summary-box">
            <h3>Total Amount</h3>
            <p>N{{ number_format($expenses->sum('amount'), 2, '.', ',') }}</p>
        </div>
        <div class="summary-box">
            <h3>Average Amount</h3>
            <p>N{{ number_format($expenses->avg('amount'), 2, '.', ',') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Expense Code</th>
                <th width="15%">Date</th>
                <th width="15%">Category</th>
                <th width="25%">Description</th>
                <th width="17%" class="text-right">Amount</th>
                <th width="18%">Created By</th>

            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td>{{ $expense->code }}</td>
                    <td>{{ $expense->date?->format('d-m-Y') }}</td>
                    <td>
                        <span class="category-badge category-{{ $expense->category->value }}">
                            {{ $expense->category->getLabel() }}
                        </span>
                    </td>
                    <td>{{ $expense->description }}</td>
                    <td class="text-right">N{{ number_format($expense->amount, 0, '.', ',') }}</td>
                    <td>{{ $expense->createdBy?->name ?? 'N/A' }}</td>

                </tr>
            @endforeach
        </tbody>
    </table>

    @if($expenses->isNotEmpty())
        <table style="margin-top: -20px;">
            <tbody>
                <tr>
                    <td colspan="4" style="border: none;"></td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px;" class="text-right">
                        <strong>Total:</strong>
                    </td>
                    <td style="border: none; border-top: 2px solid #e5e5e5; padding-top: 15px; font-weight: bold;">
                        N{{ number_format($expenses->sum('amount'), 0, '.', ',') }}
                    </td>
                    <td style="border: none;"></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>This report was generated by LfPOS Expense Management System</p>
        <p>Page {{ $currentPage ?? 1 }} of {{ $totalPages ?? 1 }}</p>
    </div>
</body>
</html>
