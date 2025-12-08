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
use OpenDxp\Config;
use OpenDxp\Maintenance\TaskInterface;
use OpenDxp\Model\Asset;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 */
class LowQualityImagePreviewTask implements TaskInterface
{
    private readonly LockInterface $lock;

    public function __construct(private readonly LoggerInterface $logger, LockFactory $lockFactory)
    {
        $this->lock = $lockFactory->createLock(self::class, 86400 * 2);
    }

    public function execute(): void
    {
        $isLowQualityPreviewEnabled = Config::getSystemConfiguration('assets')['image']['low_quality_image_preview']['enabled'];
        if (!$isLowQualityPreviewEnabled) {
            return;
        }
        if (date('H') <= 4 && $this->lock->acquire()) {
            // execution should be only sometime between 0:00 and 4:59 -> less load expected
            $this->logger->debug('Execute low quality image preview generation');

            $listing = new Asset\Listing();
            $listing->setCondition("`type` = 'image'");
            $listing->setOrderKey('id');
            $listing->setOrder('DESC');

            $total = $listing->getTotalCount();
            $perLoop = 10;

            for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
                $listing->setLimit($perLoop);
                $listing->setOffset($i * $perLoop);

                /** @var Asset\Image[] $images */
                $images = $listing->load();
                foreach ($images as $image) {
                    if (!$image->getLowQualityPreviewDataUri()) {
                        try {
                            $this->logger->debug(sprintf('Generate LQIP for asset %s', $image->getId()));
                            $image->generateLowQualityPreview();
                        } catch (Exception $e) {
                            $this->logger->error((string) $e);
                        }
                    }
                }
                OpenDxp::collectGarbage();
                OpenDxp::deleteTemporaryFiles();
            }
        } else {
            $this->logger->debug('Skip low quality image preview execution, was done within the last 24 hours');
        }
    }
}
