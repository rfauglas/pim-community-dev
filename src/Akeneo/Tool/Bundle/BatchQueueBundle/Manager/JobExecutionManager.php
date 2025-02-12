<?php

declare(strict_types=1);

namespace Akeneo\Tool\Bundle\BatchQueueBundle\Manager;

use Akeneo\Tool\Bundle\BatchQueueBundle\MessageHandler\JobExecutionMessageHandler;
use Akeneo\Tool\Component\Batch\Job\BatchStatus;
use Akeneo\Tool\Component\Batch\Job\ExitStatus;
use Akeneo\Tool\Component\Batch\Model\JobExecution;
use Akeneo\Tool\Component\BatchQueue\Queue\JobExecutionMessageInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * Repository to manage the status of a job.
 *
 * As it used by a daemon, it uses directly the DBAL to avoid any memory leak or connection problem due to the Unit of Work.
 *
 * @author    Alexandre Hocquard <alexandre.hocquard@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class JobExecutionManager
{
    private const MAX_TIME_TO_UPDATE_HEALTH_CHECK = 5;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Resolve the status of the job execution in case of crash of the daemon that launched the job.
     */
    public function resolveJobExecutionStatus(JobExecution $jobExecution): JobExecution
    {
        if (BatchStatus::STARTING === $jobExecution->getStatus()->getValue() ||
            (!$jobExecution->getExitStatus()->isRunning() && !$jobExecution->isStopping())) {
            return $jobExecution;
        }

        $healthCheck = $jobExecution->getHealthCheckTime();

        if (null === $healthCheck) {
            return $jobExecution;
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $diffInSeconds = $now->getTimestamp() - $healthCheck->getTimestamp();

        if ($diffInSeconds > JobExecutionMessageHandler::HEALTH_CHECK_INTERVAL + self::MAX_TIME_TO_UPDATE_HEALTH_CHECK) {
            $jobExecution->setStatus(new BatchStatus(BatchStatus::FAILED));
            $jobExecution->setExitStatus(new ExitStatus(ExitStatus::FAILED));
        }

        return $jobExecution;
    }

    /**
     * Get the exit status of job execution associated to a job execution message.
     */
    public function getExitStatus(JobExecutionMessageInterface $jobExecutionMessage): ?ExitStatus
    {
        $sql = 'SELECT je.exit_code FROM akeneo_batch_job_execution je WHERE je.id = :id';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $jobExecutionMessage->getJobExecutionId());
        $stmt->execute();
        $row = $stmt->fetch();

        return isset($row['exit_code']) ? new ExitStatus($row['exit_code']) : null;
    }

    /**
     * Update the status of a job execution associated to a job execution message.
     */
    public function markAsFailed(int $jobExecutionId): void
    {
        $sql = <<<SQL
UPDATE 
    akeneo_batch_job_execution je
SET 
    je.status = :status,
    je.exit_code = :exit_code,
    je.updated_time = :updated_time
WHERE
    je.id = :id;
SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $jobExecutionId);
        $stmt->bindValue('status', BatchStatus::FAILED);
        $stmt->bindValue('exit_code', ExitStatus::FAILED);
        $stmt->bindValue('updated_time', new \DateTime('now', new \DateTimeZone('UTC')), Types::DATETIME_MUTABLE);
        $stmt->execute();
    }

    /**
     * Update the health check of the job execution associated to a job execution message.
     */
    public function updateHealthCheck(JobExecutionMessageInterface $jobExecutionMessage): void
    {
        $sql = <<<SQL
UPDATE 
    akeneo_batch_job_execution je
SET 
    je.health_check_time = :health_check_time,
    je.updated_time = :updated_time
WHERE
    je.id = :id;
SQL;

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $jobExecutionMessage->getJobExecutionId());
        $stmt->bindValue('health_check_time', new \DateTime('now', new \DateTimeZone('UTC')), Types::DATETIME_MUTABLE);
        $stmt->bindValue('updated_time', new \DateTime('now', new \DateTimeZone('UTC')), Types::DATETIME_MUTABLE);
        $stmt->execute();
    }
}
