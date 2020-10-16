<?php

return [
    'access_key_id' => env('ALI_ACCESS_KEY_ID'),
    'access_key_secret' => env('ALI_ACCESS_KEY_SECRET'),
    'bucket_name' => env('ALI_OSS_BUCKET'),
    'endpoint' => env('ALI_OSS_ENDPOINT'),
    'callback_url' => env('ALI_OSS_CALLBACK'),
    'dir' => env('ALI_OSS_DEFAULT_DIR', 'others'),
    'expire' => env('ALI_OSS_EXPIRE', 30)
];