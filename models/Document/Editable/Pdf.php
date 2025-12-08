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

namespace OpenDxp\Model\Document\Editable;

use OpenDxp;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\Asset;
use Override;

/**
 * @method \OpenDxp\Model\Document\Editable\Dao getDao()
 */
class Pdf extends Model\Document\Editable implements EditmodeDataInterface
{
    /**
     * @internal
     *
     */
    protected ?int $id = null;

    public function getType(): string
    {
        return 'pdf';
    }

    public function getData(): mixed
    {
        return [
            'id' => $this->id,
        ];
    }

    #[Override]
    public function getDataForResource(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    public function getDataEditmode(): array
    {
        $pages = 0;

        if ($this->id && $asset = Asset\Document::getById($this->id)) {
            $pages = $asset->getPageCount();
        }

        return [
            'id' => $this->id,
            'pageCount' => $pages,
        ];
    }

    #[Override]
    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        $asset = $this->id ? Asset::getById($this->id) : null;
        if ($asset instanceof Asset && !array_key_exists($asset->getCacheTag(), $tags)) {
            return $asset->getCacheTags($tags);
        }

        return $tags;
    }

    #[Override]
    public function resolveDependencies(): array
    {
        $dependencies = [];

        $asset = $this->id ? Asset::getById($this->id) : null;
        if ($asset instanceof Asset) {
            $key = 'asset_' . $asset->getId();
            $dependencies[$key] = [
                'id' => $asset->getId(),
                'type' => 'asset',
            ];
        }

        return $dependencies;
    }

    #[Override]
    public function checkValidity(): bool
    {
        $sane = true;
        if (!empty($this->id)) {
            $el = Asset::getById($this->id);
            if (!$el instanceof Asset) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent asset with id [' . $this->id . ']');
                $this->id = null;
            }
        }

        return $sane;
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->id = $unserializedData['id'] ?? null;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $pdf = $data['id'] ? Asset::getById((int) $data['id']) : null;
        if ($pdf instanceof Asset\Document) {
            $this->id = $pdf->getId();
        }

        return $this;
    }

    public function frontend()
    {
        $asset = $this->id ? Asset::getById($this->id) : null;

        $config = $this->getConfig();
        $thumbnailConfig = ['width' => 1000];
        if (isset($config['thumbnail'])) {
            $thumbnailConfig = $config['thumbnail'];
        }

        if ($asset instanceof Asset\Document && $asset->getPageCount()) {
            $divId = 'opendxp-pdf-' . uniqid();
            $pdfPath = $asset->getFullPath();
            $thumbnailPath = $asset->getImageThumbnail($thumbnailConfig, 1, true);

            return <<<HTML
            <div id="$divId" class="opendxp-pdfViewer">
                <a href="$pdfPath" target="_blank"><img src="$thumbnailPath"></a>
            </div>
HTML;
        }

        return $this->getErrorCode('Preview in progress or not a valid PDF file');
    }

    private function getErrorCode(string $message = ''): string
    {
        // only display error message in debug mode
        if (!OpenDxp::inDebugMode()) {
            $message = '';
        }

        return '
        <div id="opendxp_pdf_' . $this->getName() . '" class="opendxp_editable_pdf">
            <div class="opendxp_editable_video_error" style="line-height: 50px; text-align:center; width: 100%; min-height: 50px; background: #ececec;">
                ' . $message . '
            </div>
        </div>';
    }

    public function isEmpty(): bool
    {
        return !$this->id;
    }

    public function getElement(): ?Asset
    {
        $data = $this->getData();

        return Asset::getById((int) $data['id']);
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
