<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanNotificationJob extends ilDustmanRemovalJob
{
    /**
     * @var string cron job id
     */
    public const JOB_ID = ilDustmanPlugin::PLUGIN_ID . '_notification_job';

    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return ilDustmanPlugin::PLUGIN_NAME . " Notification Job";
    }

    /**
     * @inheritDoc
     */
    public function getDescription() : string
    {
        return 'This cron job informs course- and group-object administrators about their deletion for the configured 
        amount of days before a removal date.';
    }

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
    public function run() : ilCronJobResult
    {
        if (empty($this->config->getReminderContent())) {
            $this->logger->error('[Dustman] cannot send emails without content, please configure it.');
            return new ilDustmanSuccessResult();
        }

        if (empty($this->config->getReminderInterval())) {
            $this->logger->error('[Dustman] cannot send emails without reminder interval, please configure it.');
            return new ilDustmanSuccessResult();
        }

        $interval = $this->config->getReminderInterval();
        $datetime = (1 <= $interval) ?
            (new DateTime())->add(new DateInterval("P{$interval}D")) :
            (new DateTime())
        ;

        if ($this->isExecutionDay($datetime)) {
            $this->logger->info("[Dustman] In $interval days some objects will be deleted. Dustman sends reminder E-Mails.");
            $this->sendEmails();
        } else {
            $this->logger->info("[Dustman] Today plus $interval days is not a deletion Day. Dustman does not send any emails.");
        }

        return new ilDustmanSuccessResult();
    }

    protected function sendEmails() : void
    {
        foreach ($this->getDeletableObjects() as $object) {
            $object_admins = $this->repository->getAdminsByObjectRefId((int) $object['ref_id']);
            foreach ($object_admins as $admin_id) {
                if (!ilObjUser::_exists($admin_id)) {
                    $this->logger->error("[Dustman] Administrator in object '{$object['ref_id']}' with id '$admin_id' does not exist anymore.");
                    continue;
                }

                $user = new ilObjUser($admin_id);
                $this->logger->write(
                    "[Dustman] Writing email that obj {$object['title']} ({$object['obj_id']}) 
                    will be deleted in {$this->config->getReminderInterval()} days."
                );
                $this->writeEmail($user, $object);
            }
        }
    }

    protected function writeEmail(ilObjUser $user, array $object_data) : void
    {
        global $DIC;

        /** @var $sender_factory ilMailMimeSenderFactory */
        $sender_factory = $DIC["mail.mime.sender.factory"];
        $sender_system  = $sender_factory->system();

        try {
            $mail = new ilMimeMail();
            $mail->From($sender_system);
            $mail->To($user->getEmail());
            $mail->Subject($this->config->getReminderTitle() ?? 'Reminder');
            $mail->Body($this->getEmailBody($object_data));
            $mail->Send();
        } catch (Throwable $t) {
            $this->logger->error("[Dustman] {$t->getMessage()} {$t->getTraceAsString()}");
        }
    }

    protected function getEmailBody(array $object_data) : string
    {
        return str_replace(
            [
                '[Titel]',
                '[Objekttyp]',
                '[Link]',
            ],
            [
                $object_data['title'],
                ('crs' === $object_data['type']) ? 'Kurs' : 'Gruppe',
                ilLink::_getStaticLink(
                    $object_data['ref_id'],
                    $object_data['type']
                ),
            ],
            $this->config->getReminderContent() ?? ''
        );
    }
}