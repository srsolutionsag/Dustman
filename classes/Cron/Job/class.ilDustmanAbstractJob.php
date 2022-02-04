<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
abstract class ilDustmanAbstractJob extends ilCronJob
{
    /**
     * @var ilDustmanPlugin
     */
    protected $plugin;
    /**
     * @var ilDustmanRepository
     */
    protected $repository;
    /**
     * @var ilLogger
     */
    protected $logger;
    /**
     * @var ilDustmanConfigDTO
     */
    protected $config;

    /**
     * @param ilDustmanPlugin     $plugin
     * @param ilDustmanRepository $repository
     * @param ilLogger            $logger
     */
    public function __construct(
        ilDustmanPlugin $plugin,
        ilDustmanRepository $repository,
        ilLogger $logger
    ) {
        $this->plugin = $plugin;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->config = $repository->getConfig();
    }

    /**
     * @param DateTime $datetime
     * @return bool
     */
    protected function isExecutionDay(DateTime $datetime) : bool
    {
        $today = $datetime->format('d/m/Y');
        $year  = $datetime->format('Y');

        foreach ($this->config->getExecDates() as $exec_date) {
            if ($today === "$exec_date/$year") {
                return true;
            }
        }

        return false;
    }
}