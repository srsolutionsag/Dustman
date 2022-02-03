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
    public const PLUGIN_ID = 'dustman';

    /**
     * @var string plugin display-name.
     */
    public const PLUGIN_NAME = 'Dustman';

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
            $this->getRemovalCronJob(),
        ];
    }

    /**
     * @param string $a_job_id
     * @return ilCronJob
     */
    public function getCronJobInstance($a_job_id) : ilCronJob
    {
        if (ilDustmanRemovalCronJob::JOB_ID === $a_job_id) {
            return $this->getRemovalCronJob();
        }

        return new ilDustmanNullCronJob();
    }

    /**
     * @return ilDustmanRemovalCronJob
     */
    protected function getRemovalCronJob() : ilDustmanRemovalCronJob
    {
        global $DIC;
        return new ilDustmanRemovalCronJob(
            $this,
            new ilDustmanRepository($DIC->database()),
            $DIC->logger()->root()
        );
    }
}
