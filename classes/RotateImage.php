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
     * @return bool
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

        if (!function_exists('imagerotate'))
        {
            \Message::addError(sprintf('PHP function "%s" is not installed.', 'imagerotate'));
            $this->redirect($this->getReferer());
        }

        $source = imagecreatefromjpeg(TL_ROOT . '/' . $src);

        //rotate
        $imgTmp = imagerotate($source, $angle, 0);

        // Output
        imagejpeg($imgTmp, TL_ROOT . '/' . $src);
        imagedestroy($source);

        $this->redirect($this->getReferer());

    }

}

