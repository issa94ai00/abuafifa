<?php

namespace App\Enums;

/**
 * The buckets uploaded images are filed under.
 *
 * This list used to live in three places — MediaService (which decides
 * public vs private storage), MediaController (which labels the filter
 * dropdown) and StoreMediaRequest (which validates the `in:` rule). They
 * drifted: a collection added to the request rule but not to the service
 * silently landed on the private disk and rendered as a broken image in
 * the storefront. One enum now feeds all three.
 */
enum MediaCollection: string
{
    case Default        = 'default';
    case Category       = 'category';
    case ProductCover   = 'product-cover';
    /** Legacy plural kept because existing rows are filed under it. */
    case ProductCovers  = 'product-covers';
    case ProductGallery = 'product-gallery';
    case Offer          = 'offer';
    case Gallery        = 'gallery';
    case GalleryImage   = 'gallery-image';
    case GalleryCover   = 'gallery-cover';
    case Setting        = 'setting';
    case About          = 'about';
    case Hero           = 'hero';

    public function label(): string
    {
        return match ($this) {
            self::Default        => 'عام',
            self::Category       => 'التصنيفات',
            self::ProductCover   => 'أغلفة المنتجات',
            self::ProductCovers  => 'صور المنتجات',
            self::ProductGallery => 'معرض المنتجات',
            self::Offer          => 'العروض',
            self::Gallery        => 'المعارض',
            self::GalleryImage   => 'صور المعارض',
            self::GalleryCover   => 'أغطية المعارض',
            self::Setting        => 'الإعدادات',
            self::About          => 'من نحن',
            self::Hero           => 'الواجهة الرئيسية',
        };
    }

    /**
     * Whether files in this collection may be served to anonymous
     * visitors. Everything else is written to the private disk and only
     * reachable through the signed, authorised media.file route.
     *
     * Every current collection is storefront-facing; the method exists so
     * that adding a private bucket (invoices, ID scans) is a one-line
     * change here rather than an edit to MediaService.
     */
    public function isPublic(): bool
    {
        return match ($this) {
            self::Default, self::Category, self::ProductCover, self::ProductCovers,
            self::ProductGallery, self::Offer, self::Gallery, self::GalleryImage,
            self::GalleryCover, self::Setting, self::About, self::Hero => true,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Options for an admin `<select>`: `[{value, label}]`.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * The `in:` rule body for a validation rule set.
     */
    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::values());
    }
}
