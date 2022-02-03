<?php declare(strict_types=1);

/**
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilDustmanCronJobResult extends ilCronJobResult
{
    /**
     * @param int         $status
     * @param string      $message
     * @param string|null $code
     */
    public function __construct(int $status, string $message, string $code = null)
    {
        $this->setStatus($status);
        $this->setMessage($message);
        $this->setCode($code);
    }
}
