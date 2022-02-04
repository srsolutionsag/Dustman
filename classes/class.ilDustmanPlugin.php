<?php declare(strict_types=1);

/**
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanPlugin extends ilCronHookPlugin
{
    /**
     * @var string plugin id, similar to plugin.php.
     */
    public const PLUGIN_ID = 'xdustman';

    /**
     * @var string plugin display-name.
     */
    public const PLUGIN_NAME = 'Dustman';

    /**
     * @var ilLogger
     */
    protected $logger;

    /**
     * @var ilDustmanRepository
     */
    protected $repository;

    /**
     * Safely initializes dependencies, as this class will also be
     * loaded for CLI operations where they might not be available.
     */
    public function __construct()
    {
        global $DIC;

        parent::__construct();

        if ($DIC->offsetExists('ilDB') &&
            $DIC->offsetExists('ilLoggerFactory') &&
            $DIC->offsetExists('tree')
        ) {
            $this->logger = $DIC->logger()->root();
            $this->repository = new ilDustmanRepository(
                $DIC->database(),
                $DIC->repositoryTree()
            );
        }
    }

    /**
     * @return string
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @return ilCronJob[]
     */
    public function getCronJobInstances() : array
    {
        return [
            $this->loadJobInstance(ilDustmanRemovalJob::class),
            $this->loadJobInstance(ilDustmanNotificationJob::class),
        ];
    }

    /**
     * @param string $a_job_id
     * @return ilCronJob
     */
    public function getCronJobInstance($a_job_id) : ilCronJob
    {
        switch ($a_job_id) {
            case ilDustmanRemovalJob::JOB_ID:
                return $this->loadJobInstance(ilDustmanRemovalJob::class);

            case ilDustmanNotificationJob::JOB_ID:
                return $this->loadJobInstance(ilDustmanNotificationJob::class);

            default:
                return new ilDustmanNullJob();
        }
    }

    /**
     * @param string $class_name
     * @return ilCronJob
     */
    protected function loadJobInstance(string $class_name) : ilCronJob
    {
        return new $class_name(
            $this,
            $this->repository,
            $this->logger
        );
    }
}
