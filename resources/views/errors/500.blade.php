@include('errors.layout', [
    'status' => 500,
    'headline' => 'Something went wrong',
    'message' => 'An unexpected error occurred. Please try again shortly.',
])
