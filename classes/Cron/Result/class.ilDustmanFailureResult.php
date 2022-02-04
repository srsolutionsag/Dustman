<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanFailureResult extends ilCronJobResult
{
    /**
     * @param string|null $message
     */
    public function __construct(string $message = null)
    {
        $this->setMessage($message ?? "[NOK] Cron job terminated unsuccessfully.");
        $this->setStatus(self::STATUS_FAIL);
    }
}