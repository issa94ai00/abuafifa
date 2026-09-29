<?php

namespace App\Enums;

/**
 * Central list of permission names to avoid string typos.
 *
 * @method static string dashboardView()
 * @method static string productView()
 * ...
 */
enum Permission: string
{
    case DashboardView = 'dashboard.view';

    case ProductView = 'product.view';
    case ProductCreate = 'product.create';
    case ProductEdit = 'product.edit';
    case ProductDelete = 'product.delete';
    case ProductRestore = 'product.restore';

    case CategoryView = 'category.view';
    case CategoryCreate = 'category.create';
    case CategoryEdit = 'category.edit';
    case CategoryDelete = 'category.delete';

    case OfferView = 'offer.view';
    case OfferCreate = 'offer.create';
    case OfferEdit = 'offer.edit';
    case OfferDelete = 'offer.delete';

    case GalleryView = 'gallery.view';
    case GalleryManage = 'gallery.manage';

    case MediaUpload = 'media.upload';
    case MediaDelete = 'media.delete';

    case ContactView = 'contact.view';
    case ContactManage = 'contact.manage';

    case SettingView = 'setting.view';
    case SettingManage = 'setting.manage';

    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserEdit = 'user.edit';
    case UserDelete = 'user.delete';

    case RoleManage = 'role.manage';

    case ActivityView = 'activity.view';
    case ActivityPurge = 'activity.purge';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'عرض لوحة التحكم',
            self::ProductView => 'عرض المنتجات',
            self::ProductCreate => 'إضافة منتج',
            self::ProductEdit => 'تعديل منتج',
            self::ProductDelete => 'حذف منتج',
            self::ProductRestore => 'استعادة منتج',
            self::CategoryView => 'عرض التصنيفات',
            self::CategoryCreate => 'إضافة تصنيف',
            self::CategoryEdit => 'تعديل تصنيف',
            self::CategoryDelete => 'حذف تصنيف',
            self::OfferView => 'عرض العروض',
            self::OfferCreate => 'إضافة عرض',
            self::OfferEdit => 'تعديل عرض',
            self::OfferDelete => 'حذف عرض',
            self::GalleryView => 'عرض المعرض',
            self::GalleryManage => 'إدارة المعرض',
            self::MediaUpload => 'رفع الوسائط',
            self::MediaDelete => 'حذف الوسائط',
            self::ContactView => 'عرض الرسائل',
            self::ContactManage => 'إدارة الرسائل',
            self::SettingView => 'عرض الإعدادات',
            self::SettingManage => 'تعديل الإعدادات',
            self::UserView => 'عرض المستخدمين',
            self::UserCreate => 'إضافة مستخدم',
            self::UserEdit => 'تعديل مستخدم',
            self::UserDelete => 'حذف مستخدم',
            self::RoleManage => 'إدارة الصلاحيات',
            self::ActivityView => 'عرض سجل النشاطات',
            self::ActivityPurge => 'تنظيف سجل النشاطات',
        };
    }
}