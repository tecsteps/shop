@include('errors.layout', [
    'status' => 403,
    'headline' => 'Forbidden',
    'message' => $exception?->getMessage() ?: 'You do not have permission to access this page.',
])
