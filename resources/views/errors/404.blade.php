@include('errors.layout', [
    'status' => 404,
    'headline' => 'Page not found',
    'message' => $exception?->getMessage() ?: 'The page you are looking for does not exist or has been moved.',
])
