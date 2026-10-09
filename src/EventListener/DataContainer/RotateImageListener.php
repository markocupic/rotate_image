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

namespace Markocupic\RotateImage\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FilesModel;
use Contao\Message;
use Contao\System;
use Markocupic\RotateImage\Exception\RotateImageException;
use Markocupic\RotateImage\RotateImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RotateImageListener
{
    public const KEY = 'rotate_image';

    public const TOKEN_PARAMETER = 'rotate_token';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RotateImage $rotateImage,
        private readonly RequestStack $requestStack,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire(service: 'contao.csrf.token_manager')]
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire(param: 'contao.csrf_token_name')]
        private readonly string $csrfTokenName,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Disables the operation for files which are not images or which the user is not
     * allowed to edit, and adds a CSRF token to the URL.
     */
    #[AsCallback(table: 'tl_files', target: 'list.operations.rotate_image.button')]
    public function onButtonCallback(DataContainerOperation $operation): void
    {
        $path = rawurldecode((string) ($operation->getRecord()['id'] ?? ''));

        if (!$this->rotateImage->isImage($path) || !$this->isAllowed($path)) {
            $operation->disable();

            return;
        }

        $operation['href'] .= '&'.self::TOKEN_PARAMETER.'='.$this->csrfTokenManager->getToken($this->csrfTokenName)->getValue();
    }

    /**
     * Rotates the image if the file manager is called with "key=rotate_image".
     */
    #[AsCallback(table: 'tl_files', target: 'config.onload')]
    public function onLoadCallback(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || self::KEY !== $request->query->get('key')) {
            return;
        }

        $token = new CsrfToken($this->csrfTokenName, (string) $request->query->get(self::TOKEN_PARAMETER));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedException('Invalid request token. Please reload the page and try again.');
        }

        $path = rawurldecode((string) $request->query->get('id'));

        if (!$this->isAllowed($path)) {
            throw new AccessDeniedException(\sprintf('Not allowed to rotate the file "%s".', $path));
        }

        $message = $this->framework->getAdapter(Message::class);

        try {
            $this->rotateImage->rotateImage($path);
            $this->updateFileHash($path);
        } catch (RotateImageException $e) {
            $message->addError($e->getMessage());
        }

        throw new RedirectResponseException($this->framework->getAdapter(System::class)->getReferer());
    }

    private function isAllowed(string $path): bool
    {
        if ('' === $path || !Path::isRelative($path) || str_contains(Path::canonicalize($path), '..')) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FILE)
            && $this->authorizationChecker->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_PATH, $path);
    }

    private function updateFileHash(string $path): void
    {
        $model = $this->framework->getAdapter(FilesModel::class)->findByPath($path);

        if (null === $model) {
            return;
        }

        $hash = md5_file(Path::join($this->projectDir, $path));

        if (false !== $hash) {
            $model->hash = $hash;
            $model->tstamp = time();
            $model->save();
        }
    }
}
