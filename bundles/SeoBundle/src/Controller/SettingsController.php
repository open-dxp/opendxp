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

namespace OpenDxp\Bundle\SeoBundle\Controller;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationInterface;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRepository;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/site-declaration', name: 'opendxp_bundle_seo_settings_sitedeclaration', methods: ['GET'])]
    public function siteDeclarationAction(
        #[MapQueryParameter] string $name,
        SiteDeclarationRegistry $registry,
        SiteDeclarationRepository $repository,
        TranslatorInterface $translator,
    ): JsonResponse {
        $declaration = $this->getSiteDeclaration($registry, $name);

        $sites = [['id' => 0, 'domain' => $translator->trans('main_site', [], 'admin')]];
        foreach ((new Site\Listing())->load() as $site) {
            $sites[] = ['id' => $site->getId(), 'domain' => $site->getMainDomain()];
        }

        $placeholders = [];
        foreach ($declaration->getPlaceholders() as $placeholder) {
            $placeholders[] = [
                'name' => $placeholder->name,
                'tooltip' => $placeholder->tooltipTranslationKey,
                'value' => $placeholder->value,
            ];
        }

        return $this->jsonResponse([
            'success' => true,
            'sites' => $sites,
            'data' => $repository->loadContentsBySiteId($declaration),
            'placeholders' => $placeholders,
            'contentType' => $declaration->getContentType(),
            'onFileSystem' => file_exists(OPENDXP_WEB_ROOT . $declaration->getPath()),
        ]);
    }

    #[Route('/site-declaration', name: 'opendxp_bundle_seo_settings_sitedeclaration_update', methods: ['PUT'])]
    public function siteDeclarationUpdateAction(
        Request $request,
        #[MapQueryParameter] string $name,
        SiteDeclarationRegistry $registry,
        SiteDeclarationRepository $repository,
    ): JsonResponse {
        $declaration = $this->getSiteDeclaration($registry, $name);

        /** @var array<int, string> $contents */
        $contents = $request->request->all('data');

        $errors = [];
        foreach ($contents as $siteId => $content) {
            if (trim($content) === '') {
                continue;
            }

            $siteErrors = $declaration->validate($content);
            if ($siteErrors !== []) {
                $errors[$siteId] = $siteErrors;
            }
        }

        if ($errors !== []) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => $errors,
            ]);
        }

        foreach ($contents as $siteId => $content) {
            $repository->saveContent($declaration, (int) $siteId, $content);
        }

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    private function getSiteDeclaration(SiteDeclarationRegistry $registry, string $name): SiteDeclarationInterface
    {
        if (!$registry->hasDeclaration($name)) {
            throw $this->createNotFoundException(sprintf('No site declaration is enabled as "%s".', $name));
        }

        $user = $this->getOpenDxpUser();
        if (!$user->isAllowed('site_declarations') && !$user->isAllowed('robots.txt')) {
            throw $this->createAccessDeniedException();
        }

        if (!$user->isAllowed('site_declarations')) {
            trigger_deprecation(
                'open-dxp/opendxp',
                '1.5',
                'The permission "robots.txt" is deprecated, grant "site_declarations" instead.',
            );
        }

        return $registry->getDeclaration($name);
    }
}
