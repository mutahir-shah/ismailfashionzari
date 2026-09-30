<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            color: #222;
        }
        .meta-info {
            margin-bottom: 15px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f4f5f7;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 9px;
            border-radius: 3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('company_name') ?: 'SalePro' }}</h2>
        <h3>{{ $reportName }}</h3>
        <p>{{ __('db.Generated on') }}: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="meta-info">
        @if(!empty($filters['starting_date']) && !empty($filters['ending_date']))
            <strong>{{ __('db.Period') }}:</strong> {{ $filters['starting_date'] }} {{ __('db.To') }} {{ $filters['ending_date'] }}<br>
        @endif
        <strong>{{ __('db.Total Records') }}:</strong> {{ count($data) }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th>{{ __('db.Type') }}</th>
                <th>{{ __('db.date') }}</th>
                <th>{{ __('db.reference') }}</th>
                @if($type === 'expense')
                    <th>{{ __('db.category') }}</th>
                @elseif($type === 'input')
                    <th>{{ __('db.Supplier') }}</th>
                    <th>{{ __('db.Tax Number') }}</th>
                @else
                    <th>{{ __('db.customer') }}</th>
                    <th>{{ __('db.Tax Number') }}</th>
                @endif
                <th>{{ __('db.Warehouse') }}</th>
                <th class="text-right">{{ __('db.Net Amount Excl. Tax') }}</th>
                @if($type !== 'expense')
                    <th class="text-right">{{ __('db.Discount') }}</th>
                @endif
                <th class="text-center">{{ __('db.Tax Name / Rate') }}</th>
                <th class="text-right">{{ __('db.Tax Amount') }}</th>
                <th class="text-right">{{ __('db.Total Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $row['is_return'] ? 'Return' : ($type === 'expense' ? 'Expense' : ($type === 'input' ? 'Purchase' : 'Sale')) }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    @if($type === 'expense')
                        <td>{{ $row['category_name'] ?? $row['contact_name'] }}</td>
                    @else
                        <td>{{ $row['contact_name'] }}</td>
                        <td>{{ $row['tax_number'] }}</td>
                    @endif
                    <td>{{ $row['warehouse_name'] }}</td>
                    <td class="text-right">{{ number_format($row['taxable_amount'], config('decimal') ?: 2) }}</td>
                    @if($type !== 'expense')
                        <td class="text-right">{{ number_format($row['discount'], config('decimal') ?: 2) }}</td>
                    @endif
                    <td class="text-center">{{ $row['tax_name_rate'] }}</td>
                    <td class="text-right">{{ number_format($row['tax_amount'], config('decimal') ?: 2) }}</td>
                    <td class="text-right">{{ number_format($row['total_amount'], config('decimal') ?: 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">{{ __('db.No tax transactions found for the selected period.') }}</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="{{ $type === 'expense' ? 5 : 6 }}" class="text-right">{{ __('db.Total') }}:</td>
                <td class="text-right">{{ number_format($totals['taxable_amount'], config('decimal') ?: 2) }}</td>
                @if($type !== 'expense')
                    <td class="text-right">{{ number_format($totals['discount'] ?? 0, config('decimal') ?: 2) }}</td>
                @endif
                <td></td>
                <td class="text-right">{{ number_format($totals['tax_amount'], config('decimal') ?: 2) }}</td>
                <td class="text-right">{{ number_format($totals['total_amount'], config('decimal') ?: 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
