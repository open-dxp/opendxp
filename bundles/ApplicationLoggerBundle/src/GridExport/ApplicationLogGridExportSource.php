<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\ApplicationLoggerBundle\GridExport;

use DateTimeImmutable;
use DateTimeZone;
use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumn;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportColumnType;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\ApplicationLoggerBundle\Grid\LogGridQueryFactory;
use OpenDxp\Bundle\ApplicationLoggerBundle\Security\ApplicationLoggerPermission;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Exports the entries of the application log that the filters of the log grid find. The archive tables stay out.
 */
#[AsGridExportSource(name: 'application-log', permission: ApplicationLoggerPermission::ApplicationLogging->value)]
final class LogGridExportSource implements GridExportSourceInterface
{
    public function __construct(
        private readonly LogGridQueryFactory $queryFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getColumns(GridExportQuery $query): array
    {
        $label = fn (string $key): string => $this->translator->trans($key, domain: 'admin', locale: $query->language);

        return [
            new GridExportColumn('timestamp', $label('log_timestamp'), GridExportColumnType::DATETIME),
            new GridExportColumn('pid', $label('log_pid'), GridExportColumnType::INTEGER),
            new GridExportColumn('message', $label('log_message')),
            new GridExportColumn('priority', $label('log_type')),
            new GridExportColumn('fileobject', $label('log_fileobject')),
            new GridExportColumn('relatedobject', $label('log_relatedobject')),
            new GridExportColumn('component', $label('log_component')),
            new GridExportColumn('source', $label('log_source')),
        ];
    }

    public function countRows(GridExportQuery $query): int
    {
        return (int) $this->queryFactory
            ->create($query->parameters)
            ->select('COUNT(id)')
            ->resetOrderBy()
            ->executeQuery()
            ->fetchOne();
    }

    public function getRows(GridExportQuery $query, int $offset, int $limit): iterable
    {
        $entries = $this->queryFactory
            ->create($query->parameters)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        $utc = new DateTimeZone('UTC');
        $timezone = new DateTimeZone($query->timezone);

        foreach ($entries as $entry) {
            $fileObject = null;
            if ($entry['fileobject']) {
                $fileObject = str_replace(OPENDXP_PROJECT_ROOT, '', $entry['fileobject']);
            }

            $relatedObject = null;
            if ($entry['relatedobject']) {
                $relatedObject = sprintf('%s %s', $entry['relatedobjecttype'], $entry['relatedobject']);
            }

            yield [
                'timestamp' => (new DateTimeImmutable($entry['timestamp'], $utc))->setTimezone($timezone),
                'pid' => $entry['pid'] === null ? null : (int) $entry['pid'],
                'message' => $entry['message'],
                'priority' => $entry['priority'],
                'fileobject' => $fileObject,
                'relatedobject' => $relatedObject,
                'component' => $entry['component'],
                'source' => $entry['source'],
            ];
        }
    }
}
