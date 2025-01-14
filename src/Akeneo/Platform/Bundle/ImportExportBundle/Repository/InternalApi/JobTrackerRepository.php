<?php

namespace Akeneo\Platform\Bundle\ImportExportBundle\Repository\InternalApi;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PimDataGridBundle\Doctrine\ORM\Repository\DatagridRepositoryInterface;

/**
 * Job tracker repository
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobTrackerRepository extends EntityRepository implements DatagridRepositoryInterface
{
    /**
     * @param EntityManager $em
     * @param string        $class
     */
    public function __construct(EntityManager $em, $class)
    {
        parent::__construct($em, $em->getClassMetadata($class));
    }

    /**
     * {@inheritdoc}
     */
    public function createDatagridQueryBuilder()
    {
        $qb = $this->createQueryBuilder('e');
        $qb
            ->addSelect('e.id')
            ->addSelect('j.type AS type')
            ->addSelect('e.status AS status')
            ->addSelect(
                "CONCAT('pim_import_export.batch_status.', e.status) as statusLabel"
            )
            ->addSelect('e.startTime as startTime')
            ->addSelect('j.label AS jobLabel')
            ->addSelect('j.jobName AS jobName')
            ->addSelect('e.user AS user')
            ->addSelect('SUM(s.warningCount) as warningCount')
            ->innerJoin('e.jobInstance', 'j')
            ->leftJoin('e.stepExecutions', 's')
            ->groupBy('e.id');

        return $qb;
    }
}
