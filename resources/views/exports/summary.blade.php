<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Workspace summary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1, h2 { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #bbb; padding: 6px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Workspace summary</h1>
    <p>Generated {{ $generatedAt }}@if ($actor !== null) for {{ $actor->name }}@endif.</p>

    <h2>Counts</h2>
    <table>
        <thead><tr><th>Users</th><th>Roles</th><th>Unread notifications</th><th>Activity logs</th></tr></thead>
        <tbody><tr><td>{{ $summary['counts']['users'] }}</td><td>{{ $summary['counts']['roles'] }}</td><td>{{ $summary['counts']['unreadNotifications'] }}</td><td>{{ $summary['counts']['activityLogs'] }}</td></tr></tbody>
    </table>

    <h2>Recent users</h2>
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Created</th></tr></thead>
        <tbody>
            @forelse ($summary['recentUsers'] as $user)
                <tr><td>{{ $user['name'] }}</td><td>{{ $user['email'] }}</td><td>{{ implode(', ', $user['roles']) }}</td><td>{{ $user['createdAt'] }}</td></tr>
            @empty
                <tr><td colspan="4">No users available.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Recent activity</h2>
    <table>
        <thead><tr><th>Event</th><th>Description</th><th>Created</th></tr></thead>
        <tbody>
            @forelse ($summary['recentEvents'] as $event)
                <tr><td>{{ $event['event'] }}</td><td>{{ $event['description'] }}</td><td>{{ $event['createdAt'] }}</td></tr>
            @empty
                <tr><td colspan="3">No activity available.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
