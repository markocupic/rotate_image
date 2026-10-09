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

namespace Markocupic\RotateImage\Tests\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Markocupic\RotateImage\EventListener\DataContainer\RotateImageListener;
use Markocupic\RotateImage\RotateImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RotateImageListenerTest extends TestCase
{
    public function testAddsTheTokenToTheOperation(): void
    {
        $operation = $this->getOperation('files/image.png');

        $this->getListener(isImage: true, granted: true)->onButtonCallback($operation);

        $this->assertSame('key=rotate_image&rotate_token=token-value', $operation['href']);
        $this->assertSame('bundles/markocupicrotateimage/images/rotate.svg', $operation['icon']);
    }

    public function testDisablesTheOperationForOtherFiles(): void
    {
        $operation = $this->getOperation('files/document.pdf');

        $this->getListener(isImage: false, granted: true)->onButtonCallback($operation);

        $this->assertFalse(isset($operation['href']));
        $this->assertSame('bundles/markocupicrotateimage/images/rotate--disabled.svg', $operation['icon']);
    }

    public function testDisablesTheOperationWithoutPermission(): void
    {
        $operation = $this->getOperation('files/image.png');

        $this->getListener(isImage: true, granted: false)->onButtonCallback($operation);

        $this->assertFalse(isset($operation['href']));
    }

    public function testIgnoresOtherRequests(): void
    {
        $rotateImage = $this->createMock(RotateImage::class);
        $rotateImage
            ->expects($this->never())
            ->method('rotateImage')
        ;

        $this->getListener(rotateImage: $rotateImage, request: new Request(['id' => 'files/image.png']))->onLoadCallback();
    }

    public function testDeniesRequestsWithAnInvalidToken(): void
    {
        $rotateImage = $this->createMock(RotateImage::class);
        $rotateImage
            ->expects($this->never())
            ->method('rotateImage')
        ;

        $request = new Request(['key' => 'rotate_image', 'id' => 'files/image.png', 'rotate_token' => 'invalid']);

        $this->expectException(AccessDeniedException::class);

        $this->getListener(rotateImage: $rotateImage, request: $request)->onLoadCallback();
    }

    public function testDeniesPathsOutsideTheProject(): void
    {
        $rotateImage = $this->createMock(RotateImage::class);
        $rotateImage
            ->expects($this->never())
            ->method('rotateImage')
        ;

        $request = new Request(['key' => 'rotate_image', 'id' => '../config/image.png', 'rotate_token' => 'token-value']);

        $this->expectException(AccessDeniedException::class);

        $this->getListener(rotateImage: $rotateImage, request: $request)->onLoadCallback();
    }

    private function getOperation(string $path): DataContainerOperation
    {
        return new DataContainerOperation(
            'rotate_image',
            ['href' => 'key=rotate_image', 'icon' => 'bundles/markocupicrotateimage/images/rotate.svg'],
            ['id' => rawurlencode($path)],
            $this->createMock(DataContainer::class),
        );
    }

    private function getListener(bool $isImage = true, bool $granted = true, RotateImage|null $rotateImage = null, Request|null $request = null): RotateImageListener
    {
        if (null === $rotateImage) {
            $rotateImage = $this->createMock(RotateImage::class);
            $rotateImage
                ->method('isImage')
                ->willReturn($isImage)
            ;
        }

        $requestStack = new RequestStack();

        if (null !== $request) {
            $requestStack->push($request);
        }

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturn($granted)
        ;

        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager
            ->method('getToken')
            ->willReturn(new CsrfToken('contao_csrf_token', 'token-value'))
        ;

        $tokenManager
            ->method('isTokenValid')
            ->willReturnCallback(static fn (CsrfToken $token): bool => 'token-value' === $token->getValue())
        ;

        return new RotateImageListener(
            $this->createMock(ContaoFramework::class),
            $rotateImage,
            $requestStack,
            $authorizationChecker,
            $tokenManager,
            'contao_csrf_token',
            '/var/www/project',
        );
    }
}
