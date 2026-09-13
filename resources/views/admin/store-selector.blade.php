<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select Store</title>
    <style>body{font-family:system-ui,sans-serif;max-width:28rem;margin:4rem auto;padding:0 1rem}li{margin:.5rem 0}button{padding:.5rem 1rem}</style>
</head>
<body>
    <h1>Select a store</h1>

    <ul>
        @foreach ($stores as $store)
            <li>
                <form method="POST" action="{{ route('admin.store-selector.store') }}">
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                    <button type="submit">{{ $store->name }}</button>
                </form>
            </li>
        @endforeach
    </ul>
</body>
</html>
