-- ============================================================
--  Libo Library — MySQL Setup Script
--  شغّل الملف ده في phpMyAdmin مرة واحدة
-- ============================================================

-- 1. إنشاء الـ Database
CREATE DATABASE IF NOT EXISTS libo_library
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE libo_library;

-- 2. جدول الكتب
CREATE TABLE IF NOT EXISTS books (
    id             VARCHAR(60)   NOT NULL PRIMARY KEY,
    gutenberg_id   INT           NULL,
    title          VARCHAR(500)  NOT NULL,
    author         VARCHAR(300)  NOT NULL,
    genre          VARCHAR(50)   NOT NULL DEFAULT 'Fiction',
    language_code  VARCHAR(10)   NOT NULL DEFAULT 'en',
    cover_url      TEXT          NULL,
    description    TEXT          NULL,
    content_html   LONGTEXT      NULL,
    content_source VARCHAR(50)   NOT NULL DEFAULT 'manual',
    downloads      INT           NOT NULL DEFAULT 0,
    updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_genre (genre),
    INDEX idx_gutenberg (gutenberg_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. جدول المستخدمين
CREATE TABLE IF NOT EXISTS libo_users (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    password_hash TEXT          NOT NULL,
    points        INT           NOT NULL DEFAULT 0,
    streak        INT           NOT NULL DEFAULT 0,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. جدول البروفايلات
CREATE TABLE IF NOT EXISTS profiles (
    id                 VARCHAR(60)  NOT NULL PRIMARY KEY,
    username           VARCHAR(100) NOT NULL,
    email              VARCHAR(255) NOT NULL UNIQUE,
    avatar_text        VARCHAR(10)  NULL,
    points             INT          NOT NULL DEFAULT 0,
    streak             INT          NOT NULL DEFAULT 0,
    books_opened_count INT          NOT NULL DEFAULT 0,
    favorites_count    INT          NOT NULL DEFAULT 0,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. جدول التقييمات
CREATE TABLE IF NOT EXISTS book_ratings (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id    VARCHAR(60)  NOT NULL,
    user_email VARCHAR(255) NOT NULL DEFAULT 'anonymous',
    stars      TINYINT      NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_book_user (book_id, user_email),
    INDEX idx_book (book_id),
    CONSTRAINT chk_stars CHECK (stars BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  تم الإعداد بنجاح!
-- ============================================================
