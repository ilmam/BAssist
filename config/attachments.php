<?php

return [
    'disk' => env('ATTACHMENTS_DISK', 'local'),
    'max_kilobytes' => (int) env('ATTACHMENTS_MAX_KB', 10240),
    'extensions' => [
        'pdf', 'txt', 'csv', 'md', 'feature',
        'png', 'jpg', 'jpeg', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip',
    ],
];
