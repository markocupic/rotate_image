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

namespace Markocupic\RotateImage\Tests;

use Markocupic\RotateImage\Exception\RotateImageException;
use Markocupic\RotateImage\RotateImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class RotateImageTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = sys_get_temp_dir().'/rotate-image-test-'.bin2hex(random_bytes(4));
        (new Filesystem())->mkdir($this->projectDir.'/files');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDir);

        parent::tearDown();
    }

    public function testRotatesAnImageClockwise(): void
    {
        if (!\extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not available.');
        }

        $imagick = new \Imagick();
        $imagick->newImage(40, 20, new \ImagickPixel('red'));
        $imagick->setImageFormat('png');
        $imagick->writeImage($this->projectDir.'/files/image.png');
        $imagick->clear();

        $rotateImage = new RotateImage($this->projectDir);

        $this->assertTrue($rotateImage->isImage('files/image.png'));

        $rotateImage->rotateImage('files/image.png');

        [$width, $height] = getimagesize($this->projectDir.'/files/image.png');

        $this->assertSame(20, $width);
        $this->assertSame(40, $height);
    }

    public function testDoesNotRotateOtherFiles(): void
    {
        file_put_contents($this->projectDir.'/files/image.png', 'This is not an image.');

        $rotateImage = new RotateImage($this->projectDir);

        $this->assertFalse($rotateImage->isImage('files/image.png'));

        $this->expectException(RotateImageException::class);
        $this->expectExceptionMessage('is not an image');

        $rotateImage->rotateImage('files/image.png');
    }

    public function testFailsIfTheFileDoesNotExist(): void
    {
        $rotateImage = new RotateImage($this->projectDir);

        $this->assertFalse($rotateImage->isImage('files/missing.png'));

        $this->expectException(RotateImageException::class);
        $this->expectExceptionMessage('not found');

        $rotateImage->rotateImage('files/missing.png');
    }
}
