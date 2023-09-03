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

namespace Markocupic\RotateImage\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\File;
use Contao\Image;
use Contao\StringUtil;
use Markocupic\RotateImage\RotateImage;
use Symfony\Component\HttpFoundation\RequestStack;

class Files extends Backend
{
    private RotateImage $rotateImage;
    private RequestStack $requestStack;
    private string $projectDir;

    public function __construct(RotateImage $rotateImage, RequestStack $requestStack, string $projectDir)
    {
        $this->rotateImage = $rotateImage;
        $this->projectDir = $projectDir;
        $this->requestStack = $requestStack;

        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    #[AsCallback(table: 'tl_files', target: 'list.operations.rotate_image.button', priority: 100)]
    public function rotateImage(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        $isGdImage = false;
        $strDecoded = rawurldecode($row['id']);

        if (is_file($this->projectDir.'/'.$strDecoded)) {
            $objFile = new File($strDecoded);

            if ($objFile->isGdImage) {
                $isGdImage = true;
                $request = $this->requestStack->getCurrentRequest();

                if ('rotate_image' === $request->query->get('key') && $strDecoded === $request->query->get('id')) {
                    $this->rotateImage->rotateImage($objFile);
                }
            }
        }

        return $isGdImage ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'">'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
    }
}
