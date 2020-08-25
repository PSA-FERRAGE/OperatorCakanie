<?php

namespace App\Repository\Sapia;

use App\Entity\Sapia\SSCQT03;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


class SSCQT03Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SSCQT03::class);
    }

    /**
     * @param array  $timeInterval
     * @param string $mnemo
     *
     * @return array
     */
    public function findAllBetweenDates(array $timeInterval, string $mnemo): array
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(SSCQT03::class, 't');
        $rsm->addFieldResult('t', 'ID_LOC', 'ID_LOC');
        $rsm->addFieldResult('t', 'ID_MNEMO', 'ID_MNEMO');
        $rsm->addFieldResult('t', 'DEB_EVT', 'DEB_EVT');
        $rsm->addFieldResult('t', 'FIN_EVT', 'FIN_EVT');
        $rsm->addFieldResult('t', 'DURATION', 'DURATION');

        $sql = 'SELECT
                    ID_LOC,
                    ID_MNEMO,
                    TO_CHAR(DEB_EVT, \'YYYY-MM-DD HH24:MI:SS\') AS DEB_EVT,
                    TO_CHAR(FIN_EVT, \'YYYY-MM-DD HH24:MI:SS\') AS FIN_EVT,
                    ROUND((FIN_EVT - DEB_EVT) * 86400) AS DURATION
                FROM
                    SSC99_M1.SSCQT03
                WHERE
                    (
                        DEB_EVT >= to_date(:start, \'DD/MM/YYYY HH24:MI\') AND
                        DEB_EVT <= to_date(:end, \'DD/MM/YYYY HH24:MI\')
                    ) AND ID_MNEMO = :mnemo
                ORDER BY DEB_EVT ASC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $data  = $query->execute(
            [
                'mnemo' => $mnemo,
                'start' => $timeInterval['start']->format('j/m/Y H:i'),
                'end'   => $timeInterval['end']->format('j/m/Y H:i'),
            ],
            AbstractQuery::HYDRATE_OBJECT
        );

        return $data;
    }
}
