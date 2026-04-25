<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Running Jobs Demo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e;
            color: #fff;
            min-height: 100vh;
        }
        nav {
            background: #16162a;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2a2a4a;
        }
        nav h1 { font-size: 18px; font-weight: 600; }
        nav a { color: #6366f1; text-decoration: none; }
        nav a + a { margin-left: 16px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        #running-jobs-widget { margin-top: 20px; }
        .note {
            background: #16162a;
            border: 1px solid #2a2a4a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #9ca3af;
        }
        .note code {
            background: #0f0f1e;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e5e7eb;
        }
    </style>
</head>
<body>
<nav>
    <h1>Running Jobs Demo</h1>
    <div>
        <a href="/horizon">Horizon dashboard</a>
        <a href="/api/horizon/running-jobs" target="_blank">Raw JSON</a>
    </div>
</nav>

<div class="container">
    <div class="note">
        Auto-refresh every 3 seconds. Dispatch work via
        <code>php artisan demo:dispatch-workload</code>.
        Widget source: <code>/vendor/horizon-running-jobs/widget.js</code>
    </div>

    <div id="running-jobs-widget"></div>
</div>

<script src="{{ asset('vendor/horizon-running-jobs/widget.js') }}"></script>
<script>
    HorizonRunningJobs.init({
        container: '#running-jobs-widget',
        apiUrl: '{{ url('/api/horizon/running-jobs') }}',
        refreshInterval: 3000,
        showAllServers: true
    });
</script>
</body>
</html>
