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
     * @var array<string, mixed>
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
        $this->config = $this->loadConfig();
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadConfig() : array
    {
        return [
            ilDustmanConfig::CNF_DELETE_GROUPS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_GROUPS, false),
            ilDustmanConfig::CNF_DELETE_COURSES => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_COURSES, false),
            ilDustmanConfig::CNF_FILTER_OLDER_THAN => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_FILTER_OLDER_THAN, 0),
            ilDustmanConfig::CNF_REMINDER_IN_DAYS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_IN_DAYS, 0),
            ilDustmanConfig::CNF_REMINDER_TITLE => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_TITLE, null),
            ilDustmanConfig::CNF_REMINDER_CONTENT => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, null),
            ilDustmanConfig::CNF_REMINDER_EMAIL => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_EMAIL, null),
            ilDustmanConfig::CNF_FILTER_CATEGORIES => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, []),
            ilDustmanConfig::CNF_FILTER_KEYWORDS => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, []),
            ilDustmanConfig::CNF_EXEC_ON_DATES => $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, []),
        ];
    }

    /**
     * @param DateTime $datetime
     * @return bool
     */
    protected function isExecutionDay(DateTime $datetime) : bool
    {
        $today = $datetime->format('d/m/Y');
        $year  = $datetime->format('Y');

        foreach ($this->config[ilDustmanConfig::CNF_EXEC_ON_DATES] as $exec_date) {
            if ($today === "$exec_date/$year") {
                return true;
            }
        }

        return false;
    }
}