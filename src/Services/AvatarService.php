<?php
namespace MiniApp\Services;

class AvatarService
{
    public function upload(array $file, int $userId): array
    {
        if (empty($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Файл не был загружен.');
        }

        if (($file['size'] ?? 0) > (int) config('app.max_avatar_size')) {
            throw new \RuntimeException('Размер файла превышает 5 МБ.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = (array) config('app.allowed_avatar_mime');
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Недопустимый формат файла. Разрешены JPG, PNG, WEBP.');
        }

        $extension = $allowed[$mime];
        $token = $userId . '_' . bin2hex(random_bytes(12));
        $storedName = $token . '.' . $extension;

        $originalRelative = 'uploads/avatars/original/' . $storedName;
        $thumbRelative = 'uploads/avatars/thumb/' . $storedName;
        $originalPath = base_path($originalRelative);
        $thumbPath = base_path($thumbRelative);

        $this->ensureDirectory(dirname($originalPath));
        $this->ensureDirectory(dirname($thumbPath));

        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            throw new \RuntimeException('Не удалось сохранить файл на сервере.');
        }

        $thumbnailCreated = $this->makeThumbnailFromPath($originalPath, $thumbPath, $mime, 240, 240);

        return [
            'type' => 'avatar',
            'original_name' => $file['name'] ?? 'avatar.' . $extension,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => (int) ($file['size'] ?? 0),
            'path' => '/' . $originalRelative,
            'thumbnail_path' => $thumbnailCreated ? '/' . $thumbRelative : '/' . $originalRelative,
        ];
    }

    public function makeThumbnailFromPath(string $source, string $target, string $mime, int $maxWidth, int $maxHeight): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        switch ($mime) {
            case 'image/jpeg':
                $src = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($source);
                break;
            case 'image/webp':
                $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false;
                break;
            default:
                $src = false;
        }

        if (!$src) {
            return false;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int) floor($width * $ratio);
        $newHeight = (int) floor($height * $ratio);

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = false;
        switch ($mime) {
            case 'image/jpeg':
                $saved = imagejpeg($dst, $target, 88);
                break;
            case 'image/png':
                $saved = imagepng($dst, $target, 6);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($dst, $target, 88);
                }
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);

        return (bool) $saved;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}
