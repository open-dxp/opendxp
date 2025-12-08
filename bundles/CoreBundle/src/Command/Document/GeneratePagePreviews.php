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

namespace OpenDxp\Bundle\CoreBundle\Command\Document;

use Exception;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Db;
use OpenDxp\Model\Document;
use OpenDxp\Tool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:documents:generate-page-previews',
    description: 'Generates the previews shown in the tree on hover'
)]
class GeneratePagePreviews extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'urlPrefix',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Prefix for the document path, eg. https://example.com, if not specified, OpenDxp will try use the main domain from system settings.'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Only create preview of documents in parent hierarchy (ID|path).'
            )
            ->addOption(
                'exclude-patterns',
                'x',
                InputOption::VALUE_OPTIONAL,
                'Excludes all documents whose path property matches the regex pattern.'
            );
    }

    protected function fetchItems(InputInterface $input): Document\Listing
    {
        $docs = new Document\Listing();
        $db = Db::get();

        $parentIdOrPath = $input->getOption('parent');
        if ($parentIdOrPath) {
            if (is_numeric(($parentIdOrPath))) {
                $parent = Document::getById((int) $parentIdOrPath);
            } else {
                $parent = Document::getByPath($parentIdOrPath);
            }
            if ($parent instanceof Document) {
                $conditions[] = '`path` LIKE ' . $db->quote($parent->getRealFullPath() . '%');
            } else {
                $this->writeError($parentIdOrPath . ' is not a valid id or path!');
                exit(1);
            }
        }

        $regex = $input->getOption('exclude-patterns');
        if ($regex) {
            $conditions[] = '`path` NOT REGEXP ' . $db->quote($regex);
        }

        $filter = "type = 'page'";
        if (isset($conditions)) {
            $filter = $filter . ' AND ' . implode(' AND ', $conditions);
        }
        $docs->setCondition($filter);
        $docs->load();

        return $docs;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostUrl = $input->getOption('urlPrefix');
        if (!$hostUrl) {
            $hostUrl = Tool::getHostUrl();
        }

        if (!$hostUrl) {
            $this->io->error('Unable to determine URL prefix, please use option -u or specify a main domain in system settings');

            return 1;
        }

        $docs = $this->fetchItems($input);
        foreach ($docs as $doc) {
            /**
             * @var Document\Page $doc
             */
            try {
                $success = Document\Service::generatePagePreview($doc->getId(), null, $hostUrl);
            } catch (Exception $e) {
                $this->io->error($e->getMessage());
            }
        }

        return 0;
    }
}
