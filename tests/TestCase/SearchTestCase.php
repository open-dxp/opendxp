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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Tests\TestCase;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;
use OpenDxp\TestFoundation\Admin;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\TestCase;
use OpenDxp\Tests\Story\PermissionTree;
use OpenDxp\Tests\Value\SearchAnswer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

// InnoDB writes a full text index only on commit, so a test that searches cannot run in a transaction.
#[SkipDatabaseRollback]
abstract class SearchTestCase extends TestCase
{
    /**
     * @var class-string<PermissionTree>|null
     */
    private ?string $tree = null;

    /**
     * @var list<ElementInterface|User>
     */
    private array $written = [];

    /**
     * @param class-string<PermissionTree> $tree
     */
    protected function loadTree(string $tree): void
    {
        $tree::load();
        $this->tree = $tree;

        // Only a search reads the index, so the tree writes it here and not for every test that loads it.
        foreach ($tree::getPool('elements') as $element) {
            index($element);
        }
    }

    protected function forgetAfterwards(ElementInterface|User ...$models): void
    {
        $this->written = [
            ...$this->written,
            ...$models,
        ];
    }

    protected function searchAs(string $userName, string $type, string $query): SearchAnswer
    {
        return $this->searchPageAs($userName, $type, $query, 100);
    }

    protected function searchPageAs(string $userName, string $type, string $query, int $limit): SearchAnswer
    {
        Admin::actingAs(User::getByName($userName));

        $request = new Request([
            'type' => $type,
            'query' => $query,
            'start' => 0,
            'limit' => $limit,
        ]);
        $controller = Container::get(SearchController::class);
        $response = $controller->findAction(
            $request,
            Container::get(EventDispatcherInterface::class),
            Container::get(GridHelperService::class),
        );
        $answer = json_decode($response->getContent(), true);

        return new SearchAnswer(
            array_column($answer['data'], 'fullpath'),
            $answer['total'],
        );
    }

    /**
     * The quick search reaches every kind of element at once, so it takes no type.
     *
     * @return list<string>
     */
    protected function quickSearchAs(string $userName, string $query): array
    {
        Admin::actingAs(User::getByName($userName));

        $request = new Request([
            'query' => $query,
            'start' => 0,
            'limit' => 100,
        ]);
        $controller = Container::get(SearchController::class);
        $response = $controller->quicksearchAction(
            $request,
            Container::get(EventDispatcherInterface::class),
        );
        $answer = json_decode($response->getContent(), true);

        return array_column($answer['data'], 'fullpathList');
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->written) as $model) {
            $model->delete();
        }

        if ($this->tree !== null) {
            $this->tree::forget();
        }

        parent::tearDown();
    }
}
