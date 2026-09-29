<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Server-side image validation that does not trust anything the client
 * says about the file.
 *
 * Checks, in order:
 *   1. the upload actually succeeded and is a real uploaded file
 *   2. the size is within the configured budget
 *   3. the MIME type read from the file's own bytes is allow-listed
 *      (getClientMimeType/getClientOriginalExtension are attacker
 *      controlled and are never consulted)
 *   4. getimagesize() can decode it — a .php renamed to .jpg fails here
 *   5. the pixel dimensions are sane, which also blocks decompression
 *      bombs that are small on disk and enormous in memory
 *   6. the raw bytes contain no PHP open tag, which catches polyglot
 *      files that are a valid image *and* valid PHP
 */
class SafeImage implements ValidationRule
{
    public function __construct(
        private readonly ?int $maxSizeKb = null,
        private readonly ?array $allowedMimes = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('الملف المرفوع غير صالح.');

            return;
        }

        if (! $value->isValid()) {
            $fail('فشل رفع الملف، يرجى المحاولة مرة أخرى.');

            return;
        }

        $maxSizeKb = $this->maxSizeKb ?? (int) config('security.uploads.max_size_kb', 8192);

        if ($value->getSize() > $maxSizeKb * 1024) {
            $fail("حجم الصورة يجب ألا يتجاوز {$maxSizeKb} كيلوبايت.");

            return;
        }

        $path = $value->getRealPath();

        if (! $path || ! is_readable($path)) {
            $fail('تعذّر قراءة الملف المرفوع.');

            return;
        }

        // MIME read from the file contents, not from the request headers.
        $detected = @mime_content_type($path) ?: null;
        $allowed = $this->allowedMimes ?? (array) config('security.uploads.allowed_mimes', []);

        if (! $detected || ! in_array($detected, $allowed, true)) {
            $fail('نوع الملف غير مدعوم. الأنواع المسموحة: JPEG, PNG, WebP, AVIF.');

            return;
        }

        $info = @getimagesize($path);

        if ($info === false) {
            $fail('الملف ليس صورة صالحة.');

            return;
        }

        [$width, $height] = $info;

        $maxWidth = (int) config('security.uploads.max_width', 6000);
        $maxHeight = (int) config('security.uploads.max_height', 6000);

        if ($width < 1 || $height < 1 || $width > $maxWidth || $height > $maxHeight) {
            $fail("أبعاد الصورة يجب ألا تتجاوز {$maxWidth}×{$maxHeight} بكسل.");

            return;
        }

        if ($this->containsExecutableMarkup($path)) {
            $fail('الملف يحتوي على محتوى غير مسموح.');
        }
    }

    /**
     * Look for PHP/script markers anywhere in the file. Images never
     * legitimately contain them; polyglot payloads do.
     */
    private function containsExecutableMarkup(string $path): bool
    {
        $contents = @file_get_contents($path, false, null, 0, 512 * 1024);

        if ($contents === false) {
            return true;
        }

        foreach (['<?php', '<?=', '<script', '<%'] as $marker) {
            if (stripos($contents, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
}
