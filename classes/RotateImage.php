<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * @package Gallery Creator
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace Markocupic;

/**
 * Provide methods to rotate Images.
 *
 * @author Marko Cupic <https://github.com/markocupic>
 */
class RotateImage extends \Backend
{

    /**
     * Rotate an image clockwise by 90°
     * @throws \ImagickException
     */
    public function rotateImage()
    {
        $angle = 270;
        $src = html_entity_decode(\Input::get('id'));

        if (!file_exists(TL_ROOT . '/' . $src))
        {
            \Message::addError(sprintf('File "%s" not found.', $src));
            $this->redirect($this->getReferer());
        }

        $objFile = new \File($src);
        if (!$objFile->isGdImage)
        {
            \Message::addError(sprintf('File "%s" could not be rotated because it is not an image.', $src));
            $this->redirect($this->getReferer());
        }

        if (class_exists('Imagick') && class_exists('ImagickPixel'))
        {
            $imagick = new \Imagick();
            $imagick->readImage(TL_ROOT . '/' . $src);
            $imagick->rotateImage(new \ImagickPixel('none'), $angle);
            $imagick->writeImage(TL_ROOT . '/' . $src);
            $imagick->clear();
            $imagick->destroy();
            $this->redirect($this->getReferer());
        }
        elseif (function_exists('imagerotate'))
        {
            $source = imagecreatefromjpeg(TL_ROOT . '/' . $src);

            //rotate
            $imgTmp = imagerotate($source, $angle, 0);

            // Output
            imagejpeg($imgTmp, TL_ROOT . '/' . $src);

            imagedestroy($source);
        }
        else
        {
            Message::addError(sprintf('Please install class "%s" or php function "%s" for rotating images.', 'Imagick', 'imagerotate'));
        }
        $this->redirect($this->getReferer());
    }

}

