<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.school_name') }} - {{ config('app.name') }} - {{ $metrics['period_label'] }} Queue Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #111827;
            background: #ffffff;
        }

        h1, h2 {
            margin: 0;
            color: #800000;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .actions {
            margin-bottom: 16px;
        }

        .actions button {
            border: 0;
            background: #800000;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
        }

        .card p {
            margin: 0;
        }

        .card .label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .card .value {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            text-align: left;
            padding: 8px;
        }

        th {
            background: #f9fafb;
        }

        .section {
            margin-bottom: 18px;
        }

        .split {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        @media print {
            body {
                margin: 12mm;
            }

            .actions {
                display: none;
            }

            .section {
                break-inside: avoid;
            }

            .split {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body>
    @php
        $schoolName = config('app.school_name');
        $systemName = config('app.name');
    @endphp

    <div class="header">
        <div>
            <p style="margin: 0; font-size: 13px; color: #374151;">{{ $schoolName }}</p>
            <h1>{{ $systemName }}</h1>
            <p>{{ $metrics['period_label'] }}</p>
        </div>
        <div>
            <p>Generated: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    <div class="actions">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="grid">
        <div class="card">
            <p class="label">Total Queues</p>
            <p class="value">{{ $metrics['total_queues'] }}</p>
        </div>
        <div class="card">
            <p class="label">Completed</p>
            <p class="value">{{ $metrics['completed'] }}</p>
        </div>
        <div class="card">
            <p class="label">Completion Rate</p>
            <p class="value">{{ $metrics['completion_rate'] }}%</p>
        </div>
        <div class="card">
            <p class="label">Avg Service Time</p>
            <p class="value">{{ $metrics['average_service_minutes'] }}m</p>
        </div>
    </div>

    <div class="section">
        <h2>Status Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($metrics['status_breakdown'] as $row)
                    <tr>
                        <td>{{ ucfirst($row['status']) }}</td>
                        <td>{{ $row['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Service Category Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Service Category</th>
                    <th>Total</th>
                    <th>Completed</th>
                    <th>Waiting</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($metrics['service_category_breakdown'] as $row)
                    <tr>
                        <td>{{ $row['service_category'] }}</td>
                        <td>{{ $row['total'] }}</td>
                        <td>{{ $row['completed'] }}</td>
                        <td>{{ $row['waiting'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No category activity for this date.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="split">
        <div class="section">
            <h2>Hourly Queue Volume (07:00 - 16:00)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Hour</th>
                        <th>Queues Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($metrics['hourly_data'] as $row)
                        <tr>
                            <td>{{ $row['hour'] }}</td>
                            <td>{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Weekday Queue Trend</h2>
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Queues Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($metrics['weekday_trend'] as $row)
                        <tr>
                            <td>{{ $row['day'] }}</td>
                            <td>{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
