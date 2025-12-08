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

namespace OpenDxp\Bundle\CustomReportsBundle\Tool;

use Exception;
use JsonSerializable;
use OpenDxp;
use OpenDxp\Model;
use RuntimeException;
use stdClass;

/**
 * @internal
 *
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method void delete()
 * @method void save()
 */
class Config extends Model\AbstractModel implements JsonSerializable
{
    protected string $name = '';

    protected string $sql = '';

    protected array $dataSourceConfig = [];

    protected array $columnConfiguration = [];

    protected string $niceName = '';

    protected string $group = '';

    protected string $groupIconClass = '';

    protected string $iconClass = '';

    protected bool $menuShortcut = true;

    protected string $reportClass = '';

    protected string $chartType = '';

    protected ?string $pieColumn = null;

    protected ?string $pieLabelColumn = null;

    protected ?string $xAxis = null;

    protected null|string|array $yAxis = null;

    protected ?int $modificationDate = null;

    protected ?int $creationDate = null;

    protected bool $shareGlobally = true;

    /**
     * @var string[]
     */
    protected array $sharedUserNames = [];

    /**
     * @var string[]
     */
    protected array $sharedRoleNames = [];

    /**
     *
     *
     * @throws Exception
     */
    public static function getByName(string $name): ?Config
    {
        try {
            $report = new self();

            /** @var \OpenDxp\Bundle\CustomReportsBundle\Tool\Config\Dao $dao */
            $dao = $report->getDao();
            $dao->getByName($name);

            return $report;
        } catch (Model\Exception\NotFoundException) {
            return null;
        }
    }

    public static function getReportsList(?Model\User $user = null): array
    {
        $reports = [];

        $list = new Config\Listing();
        $items = $user ? $list->getDao()->loadForGivenUser($user) : $list->getDao()->loadList();

        foreach ($items as $item) {
            $reports[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
                'cls' => 'opendxp_treenode_disabled',
                'writeable' => $item->isWriteable(),
            ];
        }

        return $reports;
    }

    /**
     * @internal
     *
     * @deprecated Use ServiceLocator with id 'opendxp.custom_report.adapter.factories' to determine the factory for the adapter instead
     */
    public static function getAdapter(?stdClass $configuration, ?Config $fullConfig = null): Adapter\CustomReportAdapterInterface
    {
        if (!$configuration instanceof \stdClass) {
            $configuration = new stdClass();
        }

        $type = $configuration->type ?? 'sql';
        $serviceLocator = OpenDxp::getContainer()->get('opendxp.custom_report.adapter.factories');

        if (!$serviceLocator->has($type)) {
            throw new RuntimeException(sprintf('Could not find Custom Report Adapter with type %s', $type));
        }

        /** @var \OpenDxp\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterFactoryInterface $factory */
        $factory = $serviceLocator->get($type);

        return $factory->create($configuration, $fullConfig);
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function setColumnConfiguration(array $columnConfiguration): void
    {
        $this->columnConfiguration = $columnConfiguration;
    }

    public function getColumnConfiguration(): array
    {
        return $this->columnConfiguration;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroupIconClass(string $groupIconClass): void
    {
        $this->groupIconClass = $groupIconClass;
    }

    public function getGroupIconClass(): string
    {
        return $this->groupIconClass;
    }

    public function setIconClass(string $iconClass): void
    {
        $this->iconClass = $iconClass;
    }

    public function getIconClass(): string
    {
        return $this->iconClass;
    }

    public function setNiceName(string $niceName): void
    {
        $this->niceName = $niceName;
    }

    public function getNiceName(): string
    {
        return $this->niceName;
    }

    public function setMenuShortcut(bool $menuShortcut): void
    {
        $this->menuShortcut = $menuShortcut;
    }

    public function getMenuShortcut(): bool
    {
        return $this->menuShortcut;
    }

    public function setDataSourceConfig(array $dataSourceConfig): void
    {
        $this->dataSourceConfig = $dataSourceConfig;
    }

    public function getDataSourceConfig(): ?stdClass
    {
        if (isset($this->dataSourceConfig[0])) {
            $dataSourceConfig = new stdClass();
            $dataSourceConfigArray = $this->dataSourceConfig[0];

            foreach ($dataSourceConfigArray as $key => $value) {
                $dataSourceConfig->$key = $value;
            }

            return $dataSourceConfig;
        }

        return null;
    }

    public function setChartType(string $chartType): void
    {
        $this->chartType = $chartType;
    }

    public function getChartType(): string
    {
        return $this->chartType;
    }

    public function setPieColumn(?string $pieColumn): void
    {
        $this->pieColumn = $pieColumn;
    }

    public function getPieColumn(): ?string
    {
        return $this->pieColumn;
    }

    public function setXAxis(?string $xAxis): void
    {
        $this->xAxis = $xAxis;
    }

    public function getXAxis(): ?string
    {
        return $this->xAxis;
    }

    public function setYAxis(array|string|null $yAxis): void
    {
        $this->yAxis = $yAxis;
    }

    public function getYAxis(): array|string|null
    {
        return $this->yAxis;
    }

    public function setPieLabelColumn(?string $pieLabelColumn): void
    {
        $this->pieLabelColumn = $pieLabelColumn;
    }

    public function getPieLabelColumn(): ?string
    {
        return $this->pieLabelColumn;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getReportClass(): string
    {
        return $this->reportClass;
    }

    public function setReportClass(string $reportClass): void
    {
        $this->reportClass = $reportClass;
    }

    public function getShareGlobally(): bool
    {
        return $this->shareGlobally;
    }

    public function setShareGlobally(bool $shareGlobally): void
    {
        $this->shareGlobally = $shareGlobally;
    }

    /**
     * @return int[]
     */
    public function getSharedUserIds(): array
    {
        $sharedUserIds = [];
        foreach ($this->sharedUserNames as $username) {
            $user = Model\User::getByName($username);
            if ($user) {
                $sharedUserIds[] = $user->getId();
            }
        }

        return $sharedUserIds;
    }

    /**
     * @return int[]
     */
    public function getSharedRoleIds(): array
    {
        $sharedRoleIds = [];
        foreach ($this->sharedRoleNames as $name) {
            $role = Model\User\Role::getByName($name);
            if ($role) {
                $sharedRoleIds[] = $role->getId();
            }
        }

        return $sharedRoleIds;
    }

    /**
     * @return string[]
     */
    public function getSharedUserNames(): array
    {
        return $this->sharedUserNames;
    }

    /**
     * @param string[] $sharedUserNames
     */
    public function setSharedUserNames(array $sharedUserNames): void
    {
        $this->sharedUserNames = $sharedUserNames;
    }

    /**
     * @return string[]
     */
    public function getSharedRoleNames(): array
    {
        return $this->sharedRoleNames;
    }

    /**
     * @param string[] $sharedRoleNames
     */
    public function setSharedRoleNames(array $sharedRoleNames): void
    {
        $this->sharedRoleNames = $sharedRoleNames;
    }

    public function jsonSerialize(): array
    {
        $data = $this->getObjectVars();
        $data['sharedUserIds'] = $this->getSharedUserIds();
        $data['sharedRoleIds'] = $this->getSharedRoleIds();

        return $data;
    }

    #[\Override]
    public function __clone(): void
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
