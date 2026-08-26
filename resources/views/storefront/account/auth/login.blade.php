<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
    <form method="POST" action="{{ route('account.login') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        @error('email')<p>{{ $message }}</p>@enderror
        <button type="submit">Login</button>
    </form>
</body>
</html>
