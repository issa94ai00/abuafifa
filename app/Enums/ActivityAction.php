<?php

namespace App\Enums;

/**
 * Every auditable back-office action. Values are stored verbatim in the
 * `activity_logs.action` column, so they must stay stable.
 */
enum ActivityAction: string
{
    // Authentication
    case Login = 'auth.login';
    case LoginFailed = 'auth.login_failed';
    case Logout = 'auth.logout';
    case Lockout = 'auth.lockout';
    case PasswordResetRequested = 'auth.password_reset_requested';
    case PasswordReset = 'auth.password_reset';
    case PasswordChanged = 'auth.password_changed';

    // Products
    case ProductCreated = 'product.created';
    case ProductUpdated = 'product.updated';
    case ProductDeleted = 'product.deleted';
    case ProductRestored = 'product.restored';
    case ProductForceDeleted = 'product.force_deleted';

    // Categories
    case CategoryCreated = 'category.created';
    case CategoryUpdated = 'category.updated';
    case CategoryDeleted = 'category.deleted';
    case CategoryRestored = 'category.restored';

    // Offers
    case OfferCreated = 'offer.created';
    case OfferUpdated = 'offer.updated';
    case OfferDeleted = 'offer.deleted';
    case OfferRestored = 'offer.restored';

    // Galleries
    case GalleryCreated = 'gallery.created';
    case GalleryUpdated = 'gallery.updated';
    case GalleryDeleted = 'gallery.deleted';
    case GalleryRestored = 'gallery.restored';

    // Media
    case MediaUploaded = 'media.uploaded';
    case MediaUpdated = 'media.updated';
    case MediaDeleted = 'media.deleted';

    // Contact messages
    case MessageRead = 'message.read';
    case MessageDeleted = 'message.deleted';

    // Users & roles
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeleted = 'user.deleted';
    case UserRolesChanged = 'user.roles_changed';
    case UserActivated = 'user.activated';
    case UserDeactivated = 'user.deactivated';

    // Settings
    case SettingsUpdated = 'settings.updated';
    case SocialLinksUpdated = 'settings.social_links_updated';

    // Security
    case UnauthorizedAttempt = 'security.unauthorized_attempt';
    case ActivityLogPurged = 'security.activity_log_purged';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'تسجيل دخول',
            self::LoginFailed => 'محاولة دخول فاشلة',
            self::Logout => 'تسجيل خروج',
            self::Lockout => 'حظر مؤقت بعد محاولات متكررة',
            self::PasswordResetRequested => 'طلب استعادة كلمة المرور',
            self::PasswordReset => 'إعادة تعيين كلمة المرور',
            self::PasswordChanged => 'تغيير كلمة المرور',
            self::ProductCreated => 'إضافة منتج',
            self::ProductUpdated => 'تعديل منتج',
            self::ProductDeleted => 'حذف منتج',
            self::ProductRestored => 'استعادة منتج',
            self::ProductForceDeleted => 'حذف منتج نهائياً',
            self::CategoryCreated => 'إضافة تصنيف',
            self::CategoryUpdated => 'تعديل تصنيف',
            self::CategoryDeleted => 'حذف تصنيف',
            self::CategoryRestored => 'استعادة تصنيف',
            self::OfferCreated => 'إضافة عرض',
            self::OfferUpdated => 'تعديل عرض',
            self::OfferDeleted => 'حذف عرض',
            self::OfferRestored => 'استعادة عرض',
            self::GalleryCreated => 'إضافة معرض',
            self::GalleryUpdated => 'تعديل معرض',
            self::GalleryDeleted => 'حذف معرض',
            self::GalleryRestored => 'استعادة معرض',
            self::MediaUploaded => 'رفع وسائط',
            self::MediaUpdated => 'تعديل وسائط',
            self::MediaDeleted => 'حذف وسائط',
            self::MessageRead => 'قراءة رسالة',
            self::MessageDeleted => 'حذف رسالة',
            self::UserCreated => 'إضافة مستخدم',
            self::UserUpdated => 'تعديل مستخدم',
            self::UserDeleted => 'حذف مستخدم',
            self::UserRolesChanged => 'تغيير صلاحيات مستخدم',
            self::UserActivated => 'تفعيل مستخدم',
            self::UserDeactivated => 'تعطيل مستخدم',
            self::SettingsUpdated => 'تعديل الإعدادات',
            self::SocialLinksUpdated => 'تعديل روابط التواصل',
            self::UnauthorizedAttempt => 'محاولة وصول غير مصرّح',
            self::ActivityLogPurged => 'تنظيف سجل النشاطات',
        };
    }

    /**
     * Coarse grouping used for filtering the log UI.
     */
    public function group(): string
    {
        return str_contains($this->value, '.')
            ? explode('.', $this->value)[0]
            : 'general';
    }

    /**
     * Actions that should stand out in the log (security relevant).
     */
    public function isSecurityEvent(): bool
    {
        return in_array($this, [
            self::LoginFailed,
            self::Lockout,
            self::UnauthorizedAttempt,
            self::UserRolesChanged,
            self::UserDeleted,
            self::PasswordReset,
            self::ActivityLogPurged,
        ], true);
    }
}
