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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\File;
use Contao\Message;
use Contao\System;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Mime\MimeTypes;

class RotateImage
{
    // Same formats as Contao\File::isGdImage, but checked by the file content (MIME type) instead of the extension
    private const array IMAGE_MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
        'image/heic',
        'image/jxl',
    ];


    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly string          $projectDir
    )
    {
    }

    /**
     * Rotate an image clockwise by 90°.
     *
     * @throws \ImagickException
     * @throws \Exception
     */
    public function rotateImage(File|string $file, int $angle = 90): void
    {
        $this->framework->initialize();

        $path = $file instanceof File ? Path::join($this->projectDir, $file->path) : $file;
        $relativePath = Path::makeRelative($path, $this->projectDir);
        $message = $this->framework->getAdapter(Message::class);
        $controller = $this->framework->getAdapter(Controller::class);
        $system = $this->framework->getAdapter(System::class);

        if (!file_exists($path)) {
            $message->addError(sprintf('File "%s" not found.', $relativePath));
            $controller->redirect($system->getReferer());
        }

        if (!$this->isImage($path)) {
            $message->addError(sprintf('File "%s" could not be rotated because it is not an image.', $relativePath));
            $controller->redirect($system->getReferer());
        }

        try {
            $imagick = new \Imagick($path);

            try {
                $imagick->rotateImage(new \ImagickPixel('none'), $angle);
                $imagick->writeImage($path);
            } finally {
                $imagick->clear();
            }
        } catch (\ImagickException) {
            $message->addError(sprintf('Could not rotate the image "%s".', $relativePath));
        }

        $controller->redirect($system->getReferer());
    }

    private function isImage(string $path): bool
    {
        return \in_array(MimeTypes::getDefault()->guessMimeType($path), self::IMAGE_MIME_TYPES, true);
    }
}
