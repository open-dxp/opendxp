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

namespace OpenDxp\Maintenance\Tasks;

use Exception;
use OpenDxp;
use OpenDxp\Document\StaticPageGenerator;
use OpenDxp\Maintenance\TaskInterface;
use OpenDxp\Model\Document;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class StaticPagesGenerationTask implements TaskInterface
{
    public function __construct(private readonly StaticPageGenerator $generator, private readonly LoggerInterface $logger)
    {
    }

    public function execute(): void
    {
        $listing = new Document\Listing();
        $listing->setCondition("`type` = 'page'");
        $listing->setOrderKey('id');
        $listing->setOrder('DESC');

        $total = $listing->getTotalCount();
        $perLoop = 10;

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $listing->setLimit($perLoop);
            $listing->setOffset($i * $perLoop);

            /** @var Document\Page[] $pages */
            $pages = $listing->load();
            foreach ($pages as $page) {
                if ($page->getStaticGeneratorEnabled()) {
                    try {
                        $lastModified = $this->generator->getLastModified($page);
                        $generate = true;
                        if ($staticLifetime = $page->getStaticGeneratorLifetime()) {
                            $currentTime = \Carbon\Carbon::now();
                            $currentTime->subMinutes($staticLifetime);

                            if ($lastModified > $currentTime->getTimestamp()) {
                                $generate = false;
                            }
                        }

                        if ($generate) {
                            $this->generator->generate($page, ['is_cli' => true]);
                        }
                    } catch (Exception $e) {
                        $this->logger->debug('Unable to generate Static Page for document ID:' . $page->getId() . ', reason: ' . $e->getMessage());
                    }
                }
            }
            OpenDxp::collectGarbage();
        }
    }
}
