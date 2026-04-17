<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Under Maintenance</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #fff;
            color: #18181b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #09090b; color: #fafafa; }
            .subtext { color: #a1a1aa; }
        }
        .container { text-align: center; max-width: 28rem; }
        .code { font-size: 4rem; font-weight: 800; letter-spacing: -0.025em; }
        .title { font-size: 1.5rem; font-weight: 600; margin-top: 0.5rem; }
        .subtext { color: #71717a; margin-top: 1rem; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <p class="code">503</p>
        <h1 class="title">Store is currently under maintenance</h1>
        <p class="subtext">We are working on improvements. Please check back shortly.</p>
    </div>
</body>
</html>
