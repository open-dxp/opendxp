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

namespace OpenDxp\Twig\Extension;

use Exception;
use OpenDxp\Document;
use OpenDxp\Twig\Extension\Templating\OpenDxpUrl;
use OpenDxp\Video;
use Override;
use Symfony\Component\Mime\MimeTypes;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @internal
 */
class HelpersExtension extends AbstractExtension
{
    private readonly OpenDxpUrl $OpenDxpUrlHelper;

    public function __construct(OpenDxpUrl $OpenDxpUrlHelper)
    {
        $this->OpenDxpUrlHelper = $OpenDxpUrlHelper;
    }

    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', $this->basenameFilter(...)),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('opendxp_video_is_available', Video::isAvailable(...)),
            new TwigFunction('opendxp_document_is_available', Document::isAvailable(...)),
            new TwigFunction('opendxp_file_exists', is_file(...)),
            new TwigFunction('opendxp_file_extension', $this->getFileExtension(...)),
            new TwigFunction('opendxp_image_version_preview', $this->getImageVersionPreview(...)),
            new TwigFunction('opendxp_asset_version_preview', $this->getAssetVersionPreview(...)),
            new TwigFunction('opendxp_breach_attack_random_content', $this->breachAttackRandomContent(...), [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('opendxp_url', $this->OpenDxpUrlHelper, [
                'name' => 'opendxp_url',
                'is_safe' => null,
            ]),
        ];
    }

    #[Override]
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', fn ($object, $class) => $object instanceof $class),
        ];
    }

    public function basenameFilter(string $value, string $suffix = ''): string
    {
        return basename($value, $suffix);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getImageVersionPreview(string $file): string
    {
        $thumbnail = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/image-version-preview-' . uniqid() . '.png';
        $convert = \OpenDxp\Image::getInstance();
        $convert->load($file);
        $convert->contain(500, 500);
        $convert->save($thumbnail, 'png');

        $dataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($thumbnail));
        unlink($thumbnail);
        unlink($file);

        return $dataUri;
    }

    /**
     * @throws Exception
     */
    public function getAssetVersionPreview(string $file): string
    {
        $dataUri = 'data:'.MimeTypes::getDefault()->guessMimeType($file).';base64,'.base64_encode(file_get_contents($file));
        unlink($file);

        return $dataUri;
    }

    /**
     *
     * @throws Exception
     */
    public function breachAttackRandomContent(): string
    {
        $length = 50;
        $randomData = random_bytes($length);

        return '<!--'
            . substr(
                base64_encode($randomData),
                0,
                ord($randomData[$length - 1]) % 32
            )
            . '-->';
    }

    public function getFileExtension(string $fileName): string
    {
        return pathinfo($fileName, PATHINFO_EXTENSION);
    }
}
