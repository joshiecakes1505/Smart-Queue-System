<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ config('app.school_name') }} Queue Report</title>

<style>
body{
    font-family: Arial, sans-serif;
    font-size: 13px;
    margin: 30px;
    color:#111;
}

h1{
    text-align:center;
    color:#800000;
    margin-bottom:2px;
}

h2{
    color:#800000;
    margin-top:25px;
    margin-bottom:8px;
}

.header{
    text-align:center;
    margin-bottom:20px;
}

.meta{
    text-align:right;
    font-size:12px;
    margin-bottom:15px;
}

.summary{
    width:100%;
    border-collapse:collapse;
    margin-bottom:20px;
}

.summary td{
    padding:6px;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:20px;
}

th,td{
    border:1px solid #ccc;
    padding:6px;
}

th{
    background:#f2f2f2;
}

.center{
    text-align:center;
}

</style>
</head>

<body>

<div class="header">
    <h1>{{ config('app.school_name') }}</h1>
    <p>{{ config('app.name') }}</p>
    <p>{{ $metrics['period_label'] }} Queue Report</p>
</div>

<div class="meta">
    Generated: {{ now()->format('Y-m-d H:i') }}
</div>


<h2>Summary</h2>

<table class="summary">
<tr>
<td><strong>Total Queues:</strong> {{ $metrics['total_queues'] }}</td>
<td><strong>Completed:</strong> {{ $metrics['completed'] }}</td>
</tr>

<tr>
<td><strong>Completion Rate:</strong> {{ $metrics['completion_rate'] }}%</td>
<td><strong>Average Service Time:</strong> {{ $metrics['average_service_minutes'] }} minutes</td>
</tr>
</table>


<h2>Status Breakdown</h2>

<table>
<thead>
<tr>
<th>Status</th>
<th class="center">Count</th>
</tr>
</thead>

<tbody>
@foreach ($metrics['status_breakdown'] as $row)
<tr>
<td>{{ ucfirst($row['status']) }}</td>
<td class="center">{{ $row['count'] }}</td>
</tr>
@endforeach
</tbody>
</table>


<h2>Service Category Breakdown</h2>

<table>
<thead>
<tr>
<th>Service Category</th>
<th class="center">Total</th>
<th class="center">Completed</th>
<th class="center">Waiting</th>
</tr>
</thead>

<tbody>
@forelse ($metrics['service_category_breakdown'] as $row)
<tr>
<td>{{ $row['service_category'] }}</td>
<td class="center">{{ $row['total'] }}</td>
<td class="center">{{ $row['completed'] }}</td>
<td class="center">{{ $row['waiting'] }}</td>
</tr>
@empty
<tr>
<td colspan="4" class="center">No activity recorded.</td>
</tr>
@endforelse
</tbody>
</table>


<h2>Hourly Queue Volume</h2>

<table>
<thead>
<tr>
<th>Hour</th>
<th class="center">Queues Created</th>
</tr>
</thead>

<tbody>
@foreach ($metrics['hourly_data'] as $row)
<tr>
<td>{{ $row['hour'] }}</td>
<td class="center">{{ $row['count'] }}</td>
</tr>
@endforeach
</tbody>
</table>


<h2>Weekday Queue Trend</h2>

<table>
<thead>
<tr>
<th>Day</th>
<th class="center">Queues Created</th>
</tr>
</thead>

<tbody>
@foreach ($metrics['weekday_trend'] as $row)
<tr>
<td>{{ $row['day'] }}</td>
<td class="center">{{ $row['count'] }}</td>
</tr>
@endforeach
</tbody>
</table>

</body>
</html>