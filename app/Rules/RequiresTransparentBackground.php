<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class RequiresTransparentBackground implements Rule
{
    protected string $message = 'File tanda tangan harus berupa gambar PNG dengan latar belakang transparan.';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (! $value) {
            return false;
        }

        $contents = null;

        if ($value instanceof UploadedFile || (is_object($value) && method_exists($value, 'getRealPath'))) {
            $realPath = $value->getRealPath();
            if ($realPath && file_exists($realPath)) {
                $contents = @file_get_contents($realPath);
            } elseif (method_exists($value, 'get')) {
                $contents = $value->get();
            } elseif (method_exists($value, 'getContent')) {
                $contents = $value->getContent();
            }
        } elseif (is_string($value)) {
            if (file_exists($value)) {
                $contents = @file_get_contents($value);
            } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($value)) {
                $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($value);
            } elseif (\Illuminate\Support\Facades\Storage::disk('local')->exists($value)) {
                $contents = \Illuminate\Support\Facades\Storage::disk('local')->get($value);
            }
        }

        if ($contents === null || $contents === false || strlen($contents) < 8) {
            $this->message = 'File tanda tangan tidak dapat dibaca.';
            return false;
        }

        // 1. Deteksi dari isi file menggunakan getimagesizefromstring / getimagesize
        $imageInfo = @getimagesizefromstring($contents);
        if (! $imageInfo || $imageInfo[2] !== IMAGETYPE_PNG) {
            $this->message = 'Format file tanda tangan harus berupa gambar PNG.';
            return false;
        }

        // 2. Cek apakah PNG memiliki channel alpha atau informasi transparansi (tRNS)
        // Header PNG: byte ke-25 (0-indexed) menentukan tipe warna (Color Type)
        // 0: Greyscale, 2: RGB, 3: Indexed, 4: Greyscale + Alpha, 6: RGBA
        $colorType = ord($contents[25] ?? "\x00");
        $hasAlphaChannel = in_array($colorType, [4, 6], true) || strpos($contents, 'tRNS') !== false;

        if (! $hasAlphaChannel) {
            $this->message = 'File tanda tangan harus berupa gambar PNG dengan latar belakang transparan.';
            return false;
        }

        // 3. Buat resource gambar GD dari string isi file
        $image = @imagecreatefromstring($contents);
        if (! $image) {
            $this->message = 'File gambar PNG tidak valid atau rusak.';
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);
            $this->message = 'Dimensi gambar tidak valid.';
            return false;
        }

        // 4. Deteksi piksel transparan menggunakan imagecolorat
        // Pada PHP GD (imagecolorat / imagecolorsforindex):
        // Nilai alpha berkisar dari 0 (sepenuhnya opaque) sampai 127 (sepenuhnya transparan).
        // Minimal 1 piksel dengan latar belakang transparan (alpha = 127 atau cocok dengan transparent color).
        $transparentColor = imagecolortransparent($image);
        $hasTransparentPixel = false;

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color >> 24) & 0x7F;

                if ($alpha === 127 || ($transparentColor >= 0 && $color === $transparentColor)) {
                    $hasTransparentPixel = true;
                    break 2;
                }
            }
        }

        imagedestroy($image);

        if (! $hasTransparentPixel) {
            $this->message = 'File tanda tangan harus berupa gambar PNG dengan latar belakang transparan.';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}