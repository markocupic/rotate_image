<?php

declare(strict_types=1);

/*
 * This file is part of Rotate Image.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/rotate_image
 */

namespace Markocupic\RotateImage;

use Contao\Backend;
use Contao\Controller;
use Contao\File;
use Contao\Message;

class RotateImage extends Backend
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    /**
     * Rotate an image clockwise by 90°.
     *
     * @throws \ImagickException
     * @throws \Exception
     */
    public function rotateImage(File $objFile): void
    {
        if (!file_exists($this->projectDir.'/'.$objFile->path)) {
            Message::addError(sprintf('File "%s" not found.', $objFile->path));
            Controller::redirect($this->getReferer());
        }

        if (!$objFile->isGdImage) {
            Message::addError(sprintf('File "%s" could not be rotated because it is not an image.', $objFile->path));
            Controller::redirect($this->getReferer());
        }

        if (class_exists('Imagick') && class_exists('ImagickPixel')) {
            $angle = 90;
            $imagick = new \Imagick();
            $imagick->readImage($this->projectDir.'/'.$objFile->path);
            $imagick->rotateImage(new \ImagickPixel('none'), $angle);
            $imagick->writeImage($this->projectDir.'/'.$objFile->path);
            $imagick->clear();
            $imagick->destroy();
            Controller::redirect($this->getReferer());
        } elseif (\function_exists('imagerotate')) {
            $angle = 270;
            $source = imagecreatefromjpeg($this->projectDir.'/'.$objFile->path);

            //rotate
            $imgTmp = imagerotate($source, $angle, 0);

            // Output
            imagejpeg($imgTmp, $this->projectDir.'/'.$objFile->path);

            imagedestroy($source);
        } else {
            Message::addError(sprintf('Please install class "%s" or php function "%s" for rotating images.', 'Imagick', 'imagerotate'));
        }

        Controller::redirect($this->getReferer());
    }
}
