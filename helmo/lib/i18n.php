<?php
class I18n {
    private static $dict = [
        'en' => [
            'login' => 'Login',
            'username' => 'Username',
            'password' => 'Password',
            'logout' => 'Logout',
            'dashboard' => 'Dashboard',
            'invalid_credentials' => 'Invalid username or password.',
            'submit' => 'Submit',
            'language' => 'Language',
        ],
        'ar' => [
            'login' => 'تسجيل الدخول',
            'username' => 'اسم المستخدم',
            'password' => 'كلمة المرور',
            'logout' => 'تسجيل الخروج',
            'dashboard' => 'لوحة التحكم',
            'invalid_credentials' => 'اسم المستخدم أو كلمة المرور غير صحيح.',
            'submit' => 'إرسال',
            'language' => 'اللغة',
        ]
    ];

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','ar'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        if (empty($_SESSION['lang'])) $_SESSION['lang'] = 'en';
    }

    public static function t(string $key): string {
        self::start();
        $lang = $_SESSION['lang'] ?? 'en';
        return self::$dict[$lang][$key] ?? $key;
    }
}
