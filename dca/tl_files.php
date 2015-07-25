<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * File management
 */
$GLOBALS['TL_DCA']['tl_files']['list']['operations']['rotateImage'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_files']['rotateImage'],
    'href' => 'key=rotate_image',
    'icon' => 'system/modules/mcupic_rotate_image/assets/images/arrow_rotate_clockwise.png',
    'button_callback' => array('tl_files_rotate_image', 'rotateImage')
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Marko Cupic <https://github.com/markocupic>
 */
class tl_files_rotate_image extends Backend
{

    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }


    /**
     * Return the edit file button
     *
     * @param array $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function rotateImage($row, $href, $label, $title, $icon, $attributes)
    {
        $isImage = false;
        if (is_file(TL_ROOT . '/' . $row['id']))
        {
            $objFile = new File($row['id']);
            if ($objFile->isGdImage)
            {
                if(strtolower($objFile->extension) == 'jpg' || strtolower($objFile->extension) == 'jpeg')
                {
                    $isImage = true;
                }
            }
        }

        return $isImage ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . specialchars($title, false, true) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.png$/i', '_.png', $icon)) . ' ';
    }
}