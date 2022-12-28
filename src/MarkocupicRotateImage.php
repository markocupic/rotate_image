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

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicRotateImage extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
