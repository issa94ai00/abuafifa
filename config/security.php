<?php

return [

    /*
    |---------------------------------------------------------------------
    | Content Security Policy
    |---------------------------------------------------------------------
    | Set report_only=true when rolling out a policy change so violations
    | are reported without breaking the page.
    */
    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
    ],

    /*
    |---------------------------------------------------------------------
    | Back-office session policy
    |---------------------------------------------------------------------
    | idle_timeout: minutes of inactivity before an admin session is
    |               terminated (0 disables the check).
    | absolute_timeout: maximum lifetime of an admin session regardless of
    |               activity, in minutes (0 disables).
    */
    'admin_session' => [
        'idle_timeout' => (int) env('ADMIN_SESSION_IDLE_TIMEOUT', 30),
        'absolute_timeout' => (int) env('ADMIN_SESSION_ABSOLUTE_TIMEOUT', 720),
    ],

    /*
    |---------------------------------------------------------------------
    | Login throttling
    |---------------------------------------------------------------------
    | Attempts are counted per (identifier + IP) and, separately, per IP
    | so that spraying many accounts from one host is also stopped.
    */
    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 900),
        'max_attempts_per_ip' => (int) env('LOGIN_MAX_ATTEMPTS_PER_IP', 20),
    ],

    /*
    |---------------------------------------------------------------------
    | Uploads
    |---------------------------------------------------------------------
    | Uploaded images are always re-encoded, which strips EXIF payloads
    | and any code smuggled inside an otherwise valid image.
    */
    'uploads' => [
        'max_size_kb' => (int) env('UPLOAD_MAX_SIZE_KB', 8192),
        'max_width' => (int) env('UPLOAD_MAX_WIDTH', 6000),
        'max_height' => (int) env('UPLOAD_MAX_HEIGHT', 6000),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/avif'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],
    ],

    /*
    |---------------------------------------------------------------------
    | Audit trail retention (days). 0 keeps everything.
    |---------------------------------------------------------------------
    */
    'activity_log' => [
        'retention_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 365),
    ],
];
