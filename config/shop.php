<?php

return [
    'media' => [
        'image_max_bytes' => (int) env('SHOP_MEDIA_IMAGE_MAX_BYTES', 50 * 1024 * 1024),
        'video_max_bytes' => (int) env('SHOP_MEDIA_VIDEO_MAX_BYTES', 500 * 1024 * 1024),
        'signed_upload_minutes' => (int) env('SHOP_MEDIA_SIGNED_UPLOAD_MINUTES', 10),
    ],
];
