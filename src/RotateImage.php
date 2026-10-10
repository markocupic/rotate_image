<?php

declare(strict_types=1);

/*
 * This file is part of Rotate Image.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/rotate_image
 */

namespace Markocupic\RotateImage;

use Markocupic\RotateImage\Exception\RotateImageException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Mime\MimeTypes;

class RotateImage
{
    /**
     * Same formats as Contao\File::isGdImage, but checked by the file content
     * (MIME type) instead of the extension.
     */
    private const IMAGE_MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
        'image/heic',
        'image/jxl',
    ];

    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Rotates an image clockwise by the given angle (default: 90°).
     *
     * @param string $path Absolute path or path relative to the project directory
     *
     * @throws RotateImageException
     */
    public function rotateImage(string $path, int $angle = 90): void
    {
        $path = Path::makeAbsolute($path, $this->projectDir);
        $relativePath = Path::makeRelative($path, $this->projectDir);

        if (!is_file($path)) {
            throw new RotateImageException(\sprintf('File "%s" not found.', $relativePath));
        }

        if (!$this->isImage($path)) {
            throw new RotateImageException(\sprintf('File "%s" could not be rotated because it is not an image.', $relativePath));
        }

        try {
            $imagick = new \Imagick($path);

            try {
                $imagick->rotateImage(new \ImagickPixel('none'), $angle);
                $imagick->writeImage($path);
            } finally {
                $imagick->clear();
            }
        } catch (\ImagickException $e) {
            throw new RotateImageException(\sprintf('Could not rotate the image "%s".', $relativePath), 0, $e);
        }
    }

    public function isImage(string $path): bool
    {
        $path = Path::makeAbsolute($path, $this->projectDir);

        if (!is_file($path)) {
            return false;
        }

        return \in_array(MimeTypes::getDefault()->guessMimeType($path), self::IMAGE_MIME_TYPES, true);
    }
}
