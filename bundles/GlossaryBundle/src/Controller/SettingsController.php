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

namespace OpenDxp\Bundle\GlossaryBundle\Controller;

use OpenDxp\Bundle\GlossaryBundle\Model\Glossary;
use OpenDxp\Cache;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/glossary', name: 'opendxp_bundle_glossary_settings_glossary', methods: ['POST'])]
    public function glossaryAction(Request $request): JsonResponse
    {
        // check glossary permissions
        $this->checkPermission('glossary');

        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->getString('data'));

            Cache::clearTag('glossary');
            if ($request->query->getString('xaction') === 'destroy') {
                $id = $data['id'];
                $glossary = Glossary::getById($id);
                $glossary->delete();

                return $this->jsonResponse(['success' => true, 'data' => []]);
            }
            if ($request->query->getString('xaction') === 'update') {
                // save glossary
                $glossary = Glossary::getById($data['id']);
                if (!empty($data['link']) && $doc = Document::getByPath($data['link'])) {
                    $data['link'] = $doc->getId();
                }

                $glossary->setValues($data);
                $glossary->save();

                $link = $glossary->getLink();
                if (!empty($link) && (int) $link > 0 && $doc = Document::getById((int)$link)) {
                    $glossary->setLink($doc->getRealFullPath());
                }

                return $this->jsonResponse(['data' => $glossary, 'success' => true]);
            }

            if ($request->query->getString('xaction') === 'create') {
                unset($data['id']);
                // save glossary
                $glossary = new Glossary();
                if (!empty($data['link']) && $doc = Document::getByPath($data['link'])) {
                    $data['link'] = $doc->getId();
                }

                $glossary->setValues($data);
                $glossary->save();

                $link = $glossary->getLink();
                if (!empty($link) && (int) $link > 0 && $doc = Document::getById((int)$link)) {
                    $glossary->setLink($doc->getRealFullPath());
                }

                return $this->jsonResponse(['data' => $glossary->getObjectVars(), 'success' => true]);
            }
        } else {
            $list = new Glossary\Listing();
            $list->setLimit($request->request->getInt('limit', 50));
            $list->setOffset($request->request->getInt('start'));

            $sortingSettings = \OpenDxp\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings([...$request->request->all(), ...$request->query->all()]);
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($request->request->has('filter')) {
                $list->setCondition('`text` LIKE ' . $list->quote('%'.$request->request->getString('filter').'%'));
            }

            $list->load();

            $glossaries = [];
            foreach ($list->getGlossary() as $glossary) {
                $link = $glossary->getLink();
                if (!empty($link) && (int) $link > 0 && $doc = Document::getById((int)$link)) {
                    $glossary->setLink($doc->getRealFullPath());
                }

                $glossaries[] = $glossary->getObjectVars();
            }

            return $this->jsonResponse(['data' => $glossaries, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->jsonResponse(['success' => false]);
    }
}
