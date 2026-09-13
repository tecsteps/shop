<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <style>body{font-family:system-ui,sans-serif;max-width:28rem;margin:4rem auto;padding:0 1rem}label{display:block;margin:.75rem 0 .25rem}input{width:100%;padding:.5rem}button{margin-top:1rem;padding:.5rem 1rem}.error{color:#b91c1c}</style>
</head>
<body>
    <h1>Reset Password</h1>

    @if ($errors->any())
        <ul class="error">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('customer.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus>

        <label for="password">New password</label>
        <input id="password" type="password" name="password" required>

        <label for="password_confirmation">Confirm new password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <button type="submit">Reset password</button>
    </form>
</body>
</html>
