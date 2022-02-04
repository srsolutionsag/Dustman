<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanSuccessResult extends ilCronJobResult
{
    /**
     * @param string|null $message
     */
    public function __construct(string $message = null)
    {
        $this->setMessage($message ?? "[OK] Cron job terminated successfully.");
        $this->setStatus(self::STATUS_OK);
    }
}