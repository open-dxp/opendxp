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

use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Site;
use OpenDxp\Model\User;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class OpenDxpObjectExtension extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        // simple object access functions in case documents/assets/objects need to be loaded directly in the template
        return [
            new TwigFunction('opendxp_document', Document::getById(...)),
            new TwigFunction('opendxp_document_by_path', Document::getByPath(...)),
            new TwigFunction('opendxp_site', Site::getById(...)),
            new TwigFunction('opendxp_site_by_root_id', Site::getByRootId(...)),
            new TwigFunction('opendxp_site_by_domain', Site::getByDomain(...)),
            new TwigFunction('opendxp_site_is_request', Site::isSiteRequest(...)),
            new TwigFunction('opendxp_site_current', Site::getCurrentSite(...)),
            new TwigFunction('opendxp_asset', Asset::getById(...)),
            new TwigFunction('opendxp_asset_by_path', Asset::getByPath(...)),
            new TwigFunction('opendxp_object', DataObject::getById(...)),
            new TwigFunction('opendxp_object_by_path', DataObject::getByPath(...)),
            new TwigFunction('opendxp_document_wrap_hardlink', Document\Hardlink\Service::wrap(...)),
            new TwigFunction('opendxp_user', User::getById(...)),
            new TwigFunction('opendxp_object_classificationstore_group', DataObject\Classificationstore\GroupConfig::getById(...)),
            new TwigFunction('opendxp_object_classificationstore_get_field_definition_from_json', $this->getFieldDefinitionFromJson(...)),
            new TwigFunction('opendxp_object_brick_definition_key', DataObject\Objectbrick\Definition::getByKey(...)),
        ];
    }

    public function getFieldDefinitionFromJson(array|string $definition, string $type): ?DataObject\ClassDefinition\Data
    {
        if (is_json($definition)) {
            $definition = json_decode($definition, true);
        }

        return DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);
    }
}
