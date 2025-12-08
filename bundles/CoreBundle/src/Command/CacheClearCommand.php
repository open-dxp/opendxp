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

namespace OpenDxp\Bundle\CoreBundle\Command;

use OpenDxp\Cache;
use OpenDxp\Console\AbstractCommand;
use OpenDxp\Event\SystemEvents;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'opendxp:cache:clear',
    description: 'Clear caches'
)]
class CacheClearCommand extends AbstractCommand
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'tags',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only specific tags (csv list of tags)'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_NONE,
                'Only output cache'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Clear all'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        if ($input->getOption('tags')) {
            $tags = $this->prepareTags($input->getOption('tags'));
            Cache::clearTags($tags);
            $io->success('OpenDxp data cache cleared tags: ' . implode(',', $tags));
        } elseif ($input->getOption('output')) {
            Cache::clearTag('output');
            $io->success('OpenDxp output cache cleared successfully');
        } else {
            Cache::clearAll();

            $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR);

            $io->success('OpenDxp data cache cleared successfully');
        }

        return 0;
    }

    /**
     * @param string[] $tags
     *
     * @return string[]
     */
    private function prepareTags(array $tags): array
    {
        // previous implementations didn't use VALUE_IS_ARRAY and just supported csv strings, so we not iterate
        // through the input array and split values
        // -t foo -t bar,baz results in [foo, bar, baz]
        $result = [];
        foreach ($tags as $tagEntries) {
            $tagEntries = explode(',', $tagEntries);
            foreach ($tagEntries as $tagEntry) {
                $tagEntry = trim($tagEntry);

                if (!empty($tagEntry)) {
                    $result[] = $tagEntry;
                }
            }
        }

        return $result;
    }
}
