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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Model\Document;

use Exception;
use OpenDxp;
use OpenDxp\Config;
use OpenDxp\Document\Renderer\DocumentRendererInterface;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Event\Model\DocumentEvent;
use OpenDxp\Image\HtmlToImage;
use OpenDxp\Model;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Editable\IdRewriterInterface;
use OpenDxp\Model\Document\Editable\LazyLoadingInterface;
use OpenDxp\Model\Element;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\ValidationException;
use OpenDxp\Tool;
use OpenDxp\Tool\Serialize;
use Override;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \OpenDxp\Model\Document\Service\Dao getDao()
 * @method int[] getTranslations(Document $document, string $task = 'open')
 * @method void addTranslation(Document $document, Document $translation, string $language = null)
 * @method void removeTranslation(Document $document)
 * @method int getTranslationSourceId(Document $document)
 * @method void removeTranslationLink(Document $document, Document $targetDocument)
 */
class Service extends Model\Element\Service
{
    /**
     * @internal
     */
    protected array $_copyRecursiveIds = [];

    /**
     * @var Document[]
     */
    protected array $nearestPathCache;

    public function __construct(
        /**
         * @internal
         */
        protected ?Model\User $_user = null
    ) {
    }

    /**
     * Renders a document outside of a view
     *
     * Parameter order was kept for BC (useLayout before query and options).
     */
    public static function render(Document\PageSnippet $document, array $attributes = [], bool $useLayout = false, array $query = [], array $options = []): string
    {
        $container = OpenDxp::getContainer();

        $renderer = $container->get(DocumentRendererInterface::class);

        // keep useLayout compatibility
        $attributes['_useLayout'] = $useLayout;

        return $renderer->render($document, $attributes, $query, $options);
    }

    /**
     * @return Page|Document|null copied document
     *
     * @throws Exception
     */
    public function copyRecursive(Document $target, Document $source, bool $initial = true): Page|Document|null
    {
        // avoid recursion
        if ($initial) {
            $this->_copyRecursiveIds = [];
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return null;
        }

        if ($source instanceof Document\PageSnippet) {
            $source->getEditables();
        }

        $source->getProperties();

        // triggers actions before document cloning
        $event = new DocumentEvent($source, [
            'target_element' => $target,
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, DocumentEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        /** @var Document $new */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);
        $new->setChildren(null);
        $new->setKey(Element\Service::getSafeCopyName($new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(null);
        $new->setCreationDate(time());
        if ($new instanceof Page) {
            $new->setPrettyUrl(null);
        }

        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChildren(true) as $child) {
            $this->copyRecursive($new, $child, false);
        }

        $this->updateChildren($target, $new);

        // triggers actions after the complete document cloning
        $event = new DocumentEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, DocumentEvents::POST_COPY);

        return $new;
    }

    /**
     * @throws ValidationException
     */
    public function copyAsChild(Document $target, Document $source, bool $enableInheritance = false, bool $resetIndex = false, ?string $language = null): Page|Document|PageSnippet
    {
        if ($source instanceof Document\PageSnippet) {
            $source->getEditables();
        }

        $source->getProperties();

        // triggers actions before document cloning
        $event = new DocumentEvent($source, [
            'target_element' => $target,
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, DocumentEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        /**
         * @var Document $new
         */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);
        $new->setChildren(null);
        $new->setKey(Element\Service::getSafeCopyName($new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(null);
        $new->setCreationDate(time());

        if ($resetIndex) {
            // this needs to be after $new->setParentId($target->getId()); -> dependency!
            $new->setIndex($new->getDao()->getNextIndex());
        }

        if ($new instanceof Page) {
            $new->setPrettyUrl(null);
        }

        if ($enableInheritance && ($new instanceof Document\PageSnippet) && $new->supportsContentMain()) {
            $new->setEditables([]);
            $new->setMissingRequiredEditable(false);
            $new->setContentMainDocumentId($source->getId(), true);
        }

        if ($language) {
            $new->setProperty('language', 'text', $language, false, true);
        }

        $new->save();

        $this->updateChildren($target, $new);

        //link translated document
        if ($language) {
            $this->addTranslation($source, $new, $language);
        }

        // triggers actions after the complete document cloning
        $event = new DocumentEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, DocumentEvents::POST_COPY);

        return $new;
    }

    /**
     * @throws ValidationException
     */
    public function copyContents(Document $target, Document $source): Link|Page|Document|PageSnippet
    {
        // check if the type is the same
        if ($source::class !== $target::class) {
            throw new Exception('Source and target have to be the same type');
        }

        // triggers actions before document cloning
        $event = new DocumentEvent($source, [
            'target_element' => $target,
        ]);
        OpenDxp::getEventDispatcher()->dispatch($event, DocumentEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        if ($source instanceof Document\PageSnippet) {
            /** @var PageSnippet $target */
            $target->setEditables($source->getEditables());

            $target->setTemplate($source->getTemplate());
            $target->setController($source->getController());

            if ($source instanceof Document\Page) {
                /** @var Page $target */
                $target->setTitle($source->getTitle());
                $target->setDescription($source->getDescription());
            }
        } elseif ($source instanceof Document\Link) {
            /** @var Link $target */
            $target->setInternalType($source->getInternalType());
            $target->setInternal($source->getInternal());
            $target->setDirect($source->getDirect());
            $target->setLinktype($source->getLinktype());
        }

        $target->setUserModification($this->_user ? $this->_user->getId() : 0);
        $target->setProperties(self::cloneProperties($source->getProperties()));
        $target->save();

        return $target;
    }

    /**
     * @internal
     */
    public static function gridDocumentData(Document $document): array
    {
        $data = Element\Service::gridElementData($document);

        if ($document instanceof Document\Page) {
            $data['title'] = $document->getTitle();
            $data['description'] = $document->getDescription();
        } else {
            $data['title'] = '';
            $data['description'] = '';
            $data['name'] = '';
        }

        return $data;
    }

    /**
     * @internal
     */
    public static function loadAllDocumentFields(Document $doc): Document
    {
        $doc->getProperties();

        if ($doc instanceof Document\PageSnippet) {
            foreach ($doc->getEditables() as $data) {
                if ($data instanceof LazyLoadingInterface) {
                    $data->load();
                }
            }
        }

        return $doc;
    }

    #[Override]
    public static function pathExists(string $path, ?string $type = null): bool
    {
        if (!$path) {
            return false;
        }

        $path = Element\Service::correctPath($path);

        try {
            $document = new Document();
            // validate path
            if (self::isValidPath($path, 'document')) {
                return $document->getDao()->getIdByPath($path) !== null;
            }
        } catch (Exception) {
        }

        return false;
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, Document::getTypes());
    }

    /**
     * Rewrites id from source to target, $rewriteConfig contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @internal
     */
    public static function rewriteIds(Document $document, array $rewriteConfig, array $params = []): Document|PageSnippet
    {
        // rewriting elements only for snippets and pages
        if ($document instanceof Document\PageSnippet) {
            if (array_key_exists('enableInheritance', $params) && $params['enableInheritance']) {
                $editables = $document->getEditables();
                $changedEditables = [];
                $contentMain = $document->getContentMainDocument();
                if ($contentMain instanceof Document\PageSnippet) {
                    $contentMainEditables = $contentMain->getEditables();
                    foreach ($contentMainEditables as $contentMainEditable) {
                        if ($contentMainEditable instanceof IdRewriterInterface) {
                            $editable = clone $contentMainEditable;
                            $editable->rewriteIds($rewriteConfig);

                            if (Serialize::serialize($editable) !== Serialize::serialize($contentMainEditable)) {
                                $changedEditables[] = $editable;
                            }
                        }
                    }
                }

                if (count($changedEditables) > 0) {
                    $editables = $changedEditables;
                }
            } else {
                $editables = $document->getEditables();
                foreach ($editables as &$editable) {
                    if ($editable instanceof IdRewriterInterface) {
                        $editable->rewriteIds($rewriteConfig);
                    }
                }
            }

            $document->setEditables($editables);
        } elseif ($document instanceof Document\Hardlink) {
            if (array_key_exists('document', $rewriteConfig) && $document->getSourceId() && array_key_exists((int) $document->getSourceId(), $rewriteConfig['document'])) {
                $document->setSourceId($rewriteConfig['document'][(int) $document->getSourceId()]);
            }
        } elseif ($document instanceof Document\Link) {
            if (array_key_exists('document', $rewriteConfig) && $document->getLinktype() === 'internal' && $document->getInternalType() === 'document' && array_key_exists((int) $document->getInternal(), $rewriteConfig['document'])) {
                $document->setInternal($rewriteConfig['document'][(int) $document->getInternal()]);
            }
        }

        // rewriting properties
        $properties = $document->getProperties();
        foreach ($properties as &$property) {
            $property->rewriteIds($rewriteConfig);
        }
        $document->setProperties($properties);

        return $document;
    }

    /**
     * @internal
     */
    public static function getByUrl(string $url): ?Document
    {
        $urlParts = parse_url($url);
        $document = null;

        if ($urlParts['path']) {
            $document = Document::getByPath($urlParts['path']);

            // search for a page in a site
            if (!$document && isset($urlParts['host'])) {
                $sitesList = new Model\Site\Listing();
                $sitesObjects = $sitesList->load();

                foreach ($sitesObjects as $site) {
                    if (!$site->getRootDocument()) {
                        continue;
                    }
                    if (!in_array($urlParts['host'], $site->getDomains()) && $site->getMainDomain() != $urlParts['host']) {
                        continue;
                    }
                    if (!$document = Document::getByPath($site->getRootDocument() . $urlParts['path'])) {
                        continue;
                    }

                    break;
                }
            }
        }

        return $document;
    }

    #[Override]
    public static function getUniqueKey(ElementInterface $element, int $nr = 0): string
    {
        $list = new Listing();
        $list->setUnpublished(true);
        $key = Element\Service::getValidKey($element->getKey(), 'document');
        if (!$key) {
            throw new Exception('No item key set.');
        }
        if ($nr) {
            $key = $key . '_' . $nr;
        }

        $parent = $element->getParent();
        if (!$parent) {
            throw new Exception('You have to set a parent document to determine a unique Key');
        }

        if (!$element->getId()) {
            $list->setCondition('parentId = ? AND `key` = ? ', [$parent->getId(), $key]);
        } else {
            $list->setCondition('parentId = ? AND `key` = ? AND id != ? ', [$parent->getId(), $key, $element->getId()]);
        }
        $check = $list->loadIdList();
        if (!empty($check)) {
            $nr++;
            $key = self::getUniqueKey($element, $nr);
        }

        return $key;
    }

    /**
     * Get the nearest document by path. Used to match nearest document for a static route.
     *
     * @internal
     */
    public function getNearestDocumentByPath(string|Request $path, bool $ignoreHardlinks = false, array $types = []): ?Document
    {
        if ($path instanceof Request) {
            $path = urldecode($path->getPathInfo());
        }

        $cacheKey = $ignoreHardlinks . implode('-', $types);
        $document = null;

        if (isset($this->nearestPathCache[$cacheKey])) {
            $document = $this->nearestPathCache[$cacheKey];
        } else {
            $paths = ['/'];
            $tmpPaths = [];

            $pathParts = explode('/', $path);
            foreach ($pathParts as $pathPart) {
                $tmpPaths[] = $pathPart;

                $t = implode('/', $tmpPaths);
                $paths[] = $t;
            }

            $paths = array_reverse($paths);
            foreach ($paths as $p) {
                if ($document = Document::getByPath($p)) {
                    if ($types === [] || in_array($document->getType(), $types)) {
                        $document = $this->nearestPathCache[$cacheKey] = $document;

                        break;
                    }
                } elseif (Model\Site::isSiteRequest()) {
                    // also check for a pretty url in a site
                    $site = Model\Site::getCurrentSite();

                    // undo the changed made by the site detection in self::match()
                    $originalPath = preg_replace('@^' . $site->getRootPath() . '@', '', $p);

                    $sitePrettyDocId = $this->getDao()->getDocumentIdByPrettyUrlInSite($site, $originalPath);
                    if ($sitePrettyDocId && $sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                        $document = $this->nearestPathCache[$cacheKey] = $sitePrettyDoc;

                        break;
                    }
                }
            }
        }

        if ($document) {
            if (!$ignoreHardlinks && $document instanceof Document\Hardlink) {
                if ($hardLinkedDocument = Document\Hardlink\Service::getNearestChildByPath($document, $path)) {
                    $document = $hardLinkedDocument;
                } else {
                    $document = Document\Hardlink\Service::wrap($document);
                }
            }

            return $document;
        }

        return null;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public static function generatePagePreview(int $id, ?Request $request = null, ?string $hostUrl = null): bool
    {
        $filesystem = new Filesystem();
        $doc = Document\Page::getById($id);
        if (!$doc) {
            return false;
        }
        if (!$hostUrl) {
            $hostUrl = Config::getSystemConfiguration('documents')['preview_url_prefix'];
            if (empty($hostUrl)) {
                $hostUrl = Tool::getHostUrl(null, $request);
            }
        }

        $url = $hostUrl . $doc->getRealFullPath();
        $tmpFile = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/screenshot_tmp_' . $doc->getId() . '.png';
        $file = $doc->getPreviewImageFilesystemPath();

        $filesystem->mkdir(dirname($file), 0775);

        if (HtmlToImage::convert($url, $tmpFile)) {
            $im = \OpenDxp\Image::getInstance();
            $im->load($tmpFile);
            $im->scaleByWidth(800);
            $im->save($file, 'jpeg', 85);

            unlink($tmpFile);

            return true;
        }

        return false;
    }
}
