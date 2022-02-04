<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanNullJob extends ilCronJob
{
    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return '';
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
        return new ilDustmanAbstractResult(
            ilCronJobResult::STATUS_FAIL,
            "this cron job should never be executed."
        );
    }
}