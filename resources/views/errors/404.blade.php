<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found</title>
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
            .link { color: #60a5fa; }
            .link:hover { color: #93bbfd; }
        }
        .container { text-align: center; max-width: 28rem; }
        .code { font-size: 4rem; font-weight: 800; letter-spacing: -0.025em; }
        .title { font-size: 1.5rem; font-weight: 600; margin-top: 0.5rem; }
        .subtext { color: #71717a; margin-top: 1rem; line-height: 1.6; }
        .link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #2563eb;
            font-weight: 500;
            text-decoration: none;
        }
        .link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <p class="code">404</p>
        <h1 class="title">Page not found</h1>
        <p class="subtext">Sorry, the page you are looking for does not exist or has been moved.</p>
        <a href="/" class="link">Back to home</a>
    </div>
</body>
</html>
