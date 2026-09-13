<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>
    <style>body{font-family:system-ui,sans-serif;max-width:28rem;margin:4rem auto;padding:0 1rem}label{display:block;margin:.75rem 0 .25rem}input{width:100%;padding:.5rem}button{margin-top:1rem;padding:.5rem 1rem}.error{color:#b91c1c}.status{color:#15803d}</style>
</head>
<body>
    <h1>Forgot Password</h1>

    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul class="error">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('customer.password.email') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

        <button type="submit">Send reset link</button>
    </form>
</body>
</html>
