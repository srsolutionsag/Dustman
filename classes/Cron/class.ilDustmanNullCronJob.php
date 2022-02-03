<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanNullCronJob extends ilCronJob
{
    /**
     * @var string cron job id.
     */
    public const JOB_ID = ilDustmanPlugin::PLUGIN_ID . '_null_job';

    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return self::JOB_ID;
    }

    /**
     * @inheritDoc
     */
    public function hasAutoActivation() : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasFlexibleSchedule() : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_YEARLY;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleValue()
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function run() : ilCronJobResult
    {
        return new ilDustmanCronJobResult(
            ilCronJobResult::STATUS_FAIL,
            "this cron job should never be executed."
        );
    }
}