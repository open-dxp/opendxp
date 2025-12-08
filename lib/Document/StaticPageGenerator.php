<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Document;

use Exception;
use OpenDxp;
use OpenDxp\Document\Renderer\DocumentRendererInterface;
use OpenDxp\Http\Request\Resolver\StaticPageResolver;
use OpenDxp\Logger;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use OpenDxp\SystemSettingsConfig;
use OpenDxp\Tool\Storage;
use Symfony\Component\Lock\LockFactory;

class StaticPageGenerator
{
    public function __construct(
        protected DocumentRendererInterface $documentRenderer,
        private readonly LockFactory $lockFactory,
        protected SystemSettingsConfig $settingsConfig
    ) {
    }

    public function getStoragePath(Document\PageSnippet $document): string
    {
        $path = $document->getRealFullPath();

        if ($document instanceof Document\Page && $document->getPrettyUrl()) {
            $path = $document->getPrettyUrl();
        } elseif ($path === '/') {
            $path = '/%home';
        }

        $useMainDomain = \OpenDxp\Config::getSystemConfiguration('documents')['static_page_generator']['use_main_domain'];
        if ($useMainDomain) {
            $systemConfig = $this->settingsConfig->getSystemSettingsConfig();
            $mainDomain = '/' . $systemConfig['general']['domain'];
            $returnPath = '';
            $pathInfo = pathinfo($path);
            if ($pathInfo['dirname'] != '') {
                $directories = explode('/', $pathInfo['dirname']);
                $directories = array_filter($directories);
                $pathString = '';
                foreach ($directories as $directory) {
                    $pathString .= '/' . $directory;
                    $doc = Document::getByPath($pathString);
                    $site = Site::getByRootId($doc->getId());
                    if ($site instanceof Site) {
                        $mainDomain = '/' . $site->getMainDomain();
                    } else {
                        $returnPath .= '/' . $directory;
                    }
                }
                $returnPath .= '/' . $pathInfo['basename'];
            }

            return $mainDomain . $returnPath . '.html';
        }

        return $path . '.html';
    }

    public function generate(Document\PageSnippet $document, array $params = []): bool
    {
        $storagePath = $this->getStoragePath($document);

        $storage = Storage::get('document_static');
        $startTime = microtime(true);

        $lockKey = 'document_static_' . $document->getId() . '_' . md5($storagePath);

        $lock = $this->lockFactory->createLock($lockKey);

        if ($params['is_cli'] ?? false) {
            $lock->acquire(true);
        }

        try {
            if (!$response = $params['response'] ?? false) {
                $response = $this->documentRenderer->render($document, [
                    'opendxp_static_page_generator' => true,
                    StaticPageResolver::ATTRIBUTE_OPENDXP_STATIC_PAGE => true,
                ]);
            }

            $storage->write($storagePath, $response);
        } catch (Exception $e) {
            Logger::debug('Error generating static Page ' . $storagePath .': ' . $e->getMessage());

            return false;
        }

        Logger::debug('Static Page ' . $storagePath . ' generated in ' . (microtime(true) - $startTime) . ' seconds');

        if ($params['is_cli'] ?? false) {
            $lock->release();
            OpenDxp::getKernel()->getContainer()->get('services_resetter')->reset();
        }

        return true;
    }

    /**
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function remove(Document\PageSnippet $document): void
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        $storage->delete($storagePath);
    }

    public function pageExists(Document\PageSnippet $document): bool
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        return $storage->fileExists($storagePath);
    }

    public function getLastModified(Document\PageSnippet $document): ?int
    {
        $storagePath = $this->getStoragePath($document);
        $storage = Storage::get('document_static');

        if ($storage->fileExists($storagePath)) {
            return $storage->lastModified($storagePath);
        }

        return null;
    }
}
