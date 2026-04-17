@include('errors.layout', [
    'status' => 503,
    'headline' => 'Temporarily unavailable',
    'message' => $exception?->getMessage() ?: 'This store is currently unavailable. Please check back soon.',
])
