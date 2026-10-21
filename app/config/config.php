<?php
/**
 * CampusLend Central Application Configuration
 */

use App\Config\Env;

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'CampusLend'),
        'env' => Env::get('APP_ENV', 'development'),
        'debug' => (bool)Env::get('APP_DEBUG', true),
        'url' => rtrim(Env::get('APP_URL', 'http://localhost:8000'), '/'),
        'campus_name' => Env::get('CAMPUS_NAME', 'Uka Tarsadia University'),
        'email_domain' => strtolower(Env::get('CAMPUS_EMAIL_DOMAIN', 'utu.ac.in')),
        'timezone' => 'Asia/Kolkata',
    ],

    'db' => [
        'connection' => Env::get('DB_CONNECTION', 'mysql'),
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int)Env::get('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'campuslend'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'sqlite_path' => Env::get('DB_SQLITE_PATH', __DIR__ . '/../../database/campuslend.sqlite'),
    ],

    'mail' => [
        'simulation' => (bool)Env::get('MAIL_SIMULATION', true),
        'host' => Env::get('MAIL_HOST', 'smtp.gmail.com'),
        'port' => (int)Env::get('MAIL_PORT', 587),
        'username' => Env::get('MAIL_USERNAME', ''),
        'password' => Env::get('MAIL_PASSWORD', ''),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'campuslend@utu.ac.in'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'CampusLend UTU'),
    ],

    'python_service' => [
        'url' => rtrim(Env::get('PYTHON_SERVICE_URL', 'http://127.0.0.1:5000'), '/'),
        'timeout' => (float)Env::get('PYTHON_SERVICE_TIMEOUT', 2.0),
    ],

    'auth' => [
        'otp_expiry_minutes' => 10,
        'otp_max_attempts' => 5,
        'session_lifetime' => (int)Env::get('SESSION_LIFETIME', 7200),
        'remember_token_days' => 30,
        'rate_limits' => [
            'login' => ['max_attempts' => 5, 'decay_seconds' => 300],
            'otp_verify' => ['max_attempts' => 5, 'decay_seconds' => 600],
            'password_reset' => ['max_attempts' => 3, 'decay_seconds' => 900],
        ],
    ],

    'upload' => [
        'max_size_bytes' => 5 * 1024 * 1024, // 5MB
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'items_dir' => __DIR__ . '/../../public/uploads/items',
        'avatars_dir' => __DIR__ . '/../../public/uploads/avatars',
    ],
];
