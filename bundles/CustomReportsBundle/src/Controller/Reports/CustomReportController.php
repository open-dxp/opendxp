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

namespace OpenDxp\Bundle\CustomReportsBundle\Controller\Reports;

use Exception;
use OpenDxp\Bundle\CustomReportsBundle\Exception\InvalidQueryException;
use OpenDxp\Bundle\CustomReportsBundle\Tool;
use OpenDxp\Bundle\CustomReportsBundle\Tool\ReportDataQuery;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\Exception\ConfigWriteException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * @internal
 */
#[Route('/custom-report')]
class CustomReportController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/tree', name: 'opendxp_bundle_customreports_customreport_tree', methods: ['GET', 'POST'])]
    public function treeAction(): JsonResponse
    {
        $this->checkPermission('reports_config');
        $reports = Tool\Config::getReportsList();

        return $this->jsonResponse($reports);
    }

    #[Route('/portlet-report-list', name: 'opendxp_bundle_customreports_customreport_portletreportlist', methods: ['GET', 'POST'])]
    public function portletReportListAction(): JsonResponse
    {
        $this->checkPermission('reports');
        $reports = Tool\Config::getReportsList($this->getOpenDxpUser());

        return $this->jsonResponse(['data' => $reports]);
    }

    #[Route('/add', name: 'opendxp_bundle_customreports_customreport_add', methods: ['POST'])]
    public function addAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports_config');

        $success = false;
        $reportName = $request->request->getString('name');
        $this->isValidConfigName($reportName);

        $report = Tool\Config::getByName($reportName);

        if (!$report) {
            $report = new Tool\Config();
            if (!$report->isWriteable()) {
                throw new ConfigWriteException();
            }

            $report->setName($reportName);
            $report->save();

            $success = true;
        }

        return $this->jsonResponse(['success' => $success, 'id' => $report->getName()]);
    }

    #[Route('/delete', name: 'opendxp_bundle_customreports_customreport_delete', methods: ['DELETE'])]
    public function deleteAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports_config');

        $report = $this->loadReport($request->request->getString('name'));

        if (!$report->isWriteable()) {
            throw new ConfigWriteException();
        }

        $report->delete();

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/clone', name: 'opendxp_bundle_customreports_customreport_clone', methods: ['POST'])]
    public function cloneAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports_config');

        $newName = $request->request->getString('newName');
        $this->isValidConfigName($newName);
        $report = Tool\Config::getByName($newName);
        if ($report) {
            throw new Exception('report already exists');
        }

        $report = $this->loadReport($request->request->getString('name'));

        $reportData = $this->encodeJson($report);
        $reportData = $this->decodeJson($reportData);

        unset($reportData['name']);
        $reportData['name'] = $newName;

        foreach ($reportData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/get', name: 'opendxp_bundle_customreports_customreport_get', methods: ['GET'])]
    public function getAction(Request $request): JsonResponse
    {
        $this->checkPermissionsHasOneOf(['reports_config', 'reports']);

        $report = $this->loadReport($request->query->getString('name'), true);

        $data = $report->getObjectVars();
        $data['writeable'] = $report->isWriteable();

        return $this->jsonResponse($data);
    }

    #[Route('/update', name: 'opendxp_bundle_customreports_customreport_update', methods: ['PUT'])]
    public function updateAction(Request $request): JsonResponse
    {
        $reportName = $request->request->getString('name');

        $this->checkPermission('reports_config');
        $this->isValidConfigName($reportName);

        $report = $this->loadReport($reportName);

        if (!$report->isWriteable()) {
            throw new ConfigWriteException();
        }

        $data = $this->decodeJson($request->request->getString('configuration'));

        if (!is_array($data['yAxis'])) {
            $data['yAxis'] = strlen($data['yAxis'] ?? '') ? [$data['yAxis']] : [];
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($report, $setter)) {
                $report->$setter($value);
            }
        }

        $report->save();

        return $this->jsonResponse(['success' => true]);
    }

    #[Route('/column-config', name: 'opendxp_bundle_customreports_customreport_columnconfig', methods: ['POST'])]
    public function columnConfigAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports_config');

        $report = $this->loadReport($request->request->getString('name'));

        $columnConfiguration = $report->getColumnConfiguration();
        $configuration = json_decode($request->request->getString('configuration'));
        $configuration = $configuration[0] ?? null;

        $success = false;
        $errorMessage = null;

        $result = [];

        try {
            $adapter = Tool\Config::getAdapter($configuration);
            $columns = $adapter->getColumns($configuration);

            foreach ($columnConfiguration as $item) {
                $name = $item['name'];
                if (in_array($name, $columns)) {
                    $result[] = $name;
                    array_splice($columns, array_search($name, $columns), 1);
                }
            }
            foreach ($columns as $remainingColumn) {
                $result[] = $remainingColumn;
            }

            $success = true;
        } catch (InvalidQueryException $e) {
            $errorMessage = $e->getMessage();
        } catch (Throwable) {
            $errorMessage = 'Failed to load columns.';
        }

        return $this->jsonResponse([
            'success' => $success,
            'columns' => $result,
            'errorMessage' => $errorMessage,
        ]);
    }

    #[Route('/get-report-config', name: 'opendxp_bundle_customreports_customreport_getreportconfig', methods: ['GET'])]
    public function getReportConfigAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports');

        $reports = [];

        $list = new Tool\Config\Listing();
        $items = $list->getDao()->loadForGivenUser($this->getOpenDxpUser());

        foreach ($items as $report) {
            if ($report->getDataSourceConfig() !== null) {
                $reports[] = [
                    'name'           => htmlspecialchars($report->getName()),
                    'niceName'       => htmlspecialchars($report->getNiceName()),
                    'iconClass'      => htmlspecialchars($report->getIconClass()),
                    'group'          => htmlspecialchars($report->getGroup()),
                    'groupIconClass' => htmlspecialchars($report->getGroupIconClass()),
                    'menuShortcut'   => $report->getMenuShortcut(),
                    'reportClass'    => htmlspecialchars($report->getReportClass()),
                ];
            }
        }

        return $this->jsonResponse([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    #[Route('/data', name: 'opendxp_bundle_customreports_customreport_data', methods: ['POST'])]
    public function dataAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports');
        $offset = $request->request->getInt('start', 0);
        $limit = $request->request->getInt('limit', 40);
        $config = $this->loadReport($request->request->getString('name'), true);

        $configuration = $config->getDataSourceConfig();
        $adapter = Tool\Config::getAdapter($configuration, $config);
        $query = ReportDataQuery::fromParameters(
            [...$request->request->all(), ...$request->query->all()],
            $configuration,
        );
        $result = $adapter->getData(
            $query->filters,
            $query->sort,
            $query->dir,
            $offset,
            $limit,
            null,
            $query->drillDownFilters,
        );

        return $this->jsonResponse([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
        ]);
    }

    #[Route('/drill-down-options', name: 'opendxp_bundle_customreports_customreport_drilldownoptions', methods: ['POST'])]
    public function drillDownOptionsAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports');

        $field = $request->request->getString('field');
        $filters = ($request->request->getString('filter') ? json_decode($request->request->getString('filter'), true) : null);
        $drillDownFilters = $request->request->all('drillDownFilters');

        $config = $this->loadReport($request->request->getString('name'), true);

        $configuration = $config->getDataSourceConfig();

        $adapter = Tool\Config::getAdapter($configuration, $config);
        $result = $adapter->getAvailableOptions($filters ?? [], $field, $drillDownFilters);

        return $this->jsonResponse([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    #[Route('/chart', name: 'opendxp_bundle_customreports_customreport_chart', methods: ['POST'])]
    public function chartAction(Request $request): JsonResponse
    {
        $this->checkPermission('reports');
        $config = $this->loadReport($request->request->getString('name'), true);

        $configuration = $config->getDataSourceConfig();
        $adapter = Tool\Config::getAdapter($configuration, $config);
        $query = ReportDataQuery::fromParameters(
            [...$request->request->all(), ...$request->query->all()],
            $configuration,
        );
        $result = $adapter->getData(
            $query->filters,
            $query->sort,
            $query->dir,
            null,
            null,
            null,
            $query->drillDownFilters,
        );

        return $this->jsonResponse([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
        ]);
    }

    private function loadReport(string $name, bool $checkAccess = false): Tool\Config
    {
        $report = Tool\Config::getByName($name);
        if (!$report) {
            throw $this->createNotFoundException();
        }

        if ($checkAccess && !$report->isAllowedForUser($this->getOpenDxpUser())) {
            throw $this->createAccessDeniedException();
        }

        return $report;
    }

    /**
     * @throws Exception
     */
    private function isValidConfigName(string $configName): void
    {
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $configName)) {
            throw new Exception('The customer report name is invalid');
        }
    }
}
