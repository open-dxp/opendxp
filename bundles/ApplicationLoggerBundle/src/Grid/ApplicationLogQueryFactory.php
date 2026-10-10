<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\ApplicationLoggerBundle\Grid;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;

/**
 * @internal
 */
final class LogGridQueryFactory
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function create(array $parameters): QueryBuilder
    {
        $value = static fn (string $name): string => (string) ($parameters[$name] ?? '');

        $qb = $this->db->createQueryBuilder();
        $qb
            ->select('*')
            ->from(ApplicationLoggerDb::TABLE_NAME)
            ->orderBy('id', 'DESC');

        $sortingSettings = QueryParams::extractSortingSettings($parameters);
        if ($sortingSettings['orderKey']) {
            $qb->orderBy($this->db->quoteIdentifier($sortingSettings['orderKey']), $sortingSettings['order']);
        }

        $priority = $value('priority');
        if (!empty($priority)) {
            $qb->andWhere($qb->expr()->eq('priority', ':priority'));
            $qb->setParameter('priority', $priority);
        }

        $fromDate = $this->parseDateObject($value('fromDate'), $value('fromTime'));
        if ($fromDate) {
            $qb->andWhere('timestamp > :fromDate');
            $qb->setParameter('fromDate', $fromDate, Types::DATETIME_MUTABLE);
        }

        $toDate = $this->parseDateObject($value('toDate'), $value('toTime'));
        if ($toDate) {
            $qb->andWhere('timestamp <= :toDate');
            $qb->setParameter('toDate', $toDate, Types::DATETIME_MUTABLE);
        }

        if (!empty($component = $value('component'))) {
            $qb->andWhere('component = ' . $qb->createNamedParameter($component));
        }

        if (!empty($relatedObject = $value('relatedobject'))) {
            $qb->andWhere('relatedobject = ' . $qb->createNamedParameter($relatedObject));
        }

        if (!empty($message = $value('message'))) {
            $qb->andWhere('message LIKE ' . $qb->createNamedParameter('%' . $message . '%'));
        }

        if (!empty($pid = (int) $value('pid'))) {
            $qb->andWhere('pid LIKE ' . $qb->createNamedParameter('%' . $pid . '%'));
        }

        return $qb;
    }

    private function parseDateObject(string $date, string $time): ?DateTime
    {
        if ($date === '') {
            return null;
        }

        $pattern = '/^(?P<date>\d{4}\-\d{2}\-\d{2})T(?P<time>\d{2}:\d{2}:\d{2})$/';

        $dateTime = null;
        if (preg_match($pattern, $date, $dateMatches)) {
            if ($time !== '' && preg_match($pattern, $time, $timeMatches)) {
                $dateTime = new DateTime(sprintf('%sT%s', $dateMatches['date'], $timeMatches['time']));
            } else {
                $dateTime = new DateTime($date);
            }
        }

        return $dateTime;
    }
}
