<?php
declare(strict_types=1);

/**
 * إعدادات قاعدة البيانات المحلية - XAMPP / phpMyAdmin
 * ضع اسم قاعدة البيانات وكلمة السر بتاعتك هنا
 */
return [
  'DB_HOST'     => 'localhost',
  'DB_PORT'     => '3306',
  'DB_NAME'     => 'libo_library',   // ← اسم الـ database اللي هتعملها في phpMyAdmin
  'DB_USER'     => 'root',           // ← المستخدم الافتراضي في XAMPP
  'DB_PASSWORD' => '',               // ← كلمة السر (فارغة في XAMPP الافتراضي)
];
