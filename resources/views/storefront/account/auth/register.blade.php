<!DOCTYPE html>
<html>
<head><title>Register</title></head>
<body>
    <form method="POST" action="{{ route('account.register') }}">
        @csrf
        <input type="text" name="name" placeholder="Name" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="password_confirmation" placeholder="Confirm Password" required />
        @error('email')<p>{{ $message }}</p>@enderror
        <button type="submit">Register</button>
    </form>
</body>
</html>
