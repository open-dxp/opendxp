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

namespace OpenDxp\Model\Document;

use OpenDxp;
use OpenDxp\Messenger\GeneratePagePreviewMessage;
use Override;

/**
 * @method \OpenDxp\Model\Document\Page\Dao getDao()
 */
class Page extends PageSnippet
{
    /**
     * Contains the title of the page (meta-title)
     *
     * @internal
     *
     */
    protected string $title = '';

    /**
     * Contains the description of the page (meta-description)
     *
     * @internal
     *
     */
    protected string $description = '';

    protected string $type = 'page';

    /**
     * @internal
     *
     */
    protected ?string $prettyUrl = null;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return \OpenDxp\Tool\Text::removeLineBreaks($this->title);
    }

    public function setDescription(string $description): static
    {
        $this->description = str_replace("\n", ' ', $description);

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    #[Override]
    public function getFullPath(bool $force = false): string
    {
        $path = parent::getFullPath($force);

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if (!OpenDxp::inAdmin() && !($this instanceof Hardlink\Wrapper\WrapperInterface) &&\OpenDxp\Tool\Frontend::isDocumentInCurrentSite($this)) {
            // check for a pretty url
            $prettyUrl = $this->getPrettyUrl();
            if (!empty($prettyUrl) && strlen($prettyUrl) > 1) {
                return $prettyUrl;
            }
        }

        return $path;
    }

    public function setPrettyUrl(?string $prettyUrl): static
    {
        if (!$prettyUrl) {
            $this->prettyUrl = null;
        } else {
            $this->prettyUrl = '/' . trim($prettyUrl, ' /');
            if (strlen($this->prettyUrl) < 2) {
                $this->prettyUrl = null;
            }
        }

        return $this;
    }

    public function getPrettyUrl(): ?string
    {
        return $this->prettyUrl;
    }

    public function getPreviewImageFilesystemPath(): string
    {
        return OPENDXP_SYSTEM_TEMP_DIRECTORY . '/document-page-previews/document-page-screenshot-' . $this->getId() . '@2x.jpg';
    }

    #[Override]
    public function save(array $parameters = []): static
    {
        $page = parent::save($parameters);

        // Dispatch page preview message, if preview is enabled.
        $documentsConfig = \OpenDxp\Config::getSystemConfiguration('documents');
        if ($documentsConfig['generate_preview'] ?? false) {
            OpenDxp::getContainer()->get('messenger.bus.opendxp-core')->dispatch(
                new GeneratePagePreviewMessage($this->getId(), \OpenDxp\Tool::getHostUrl())
            );
        }

        return $page;
    }
}
