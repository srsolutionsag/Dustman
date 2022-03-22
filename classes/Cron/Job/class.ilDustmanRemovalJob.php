<?php declare(strict_types=1);

/**
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanRemovalJob extends ilDustmanAbstractJob
{
    /**
     * @var string cron job id.
     */
    public const JOB_ID = ilDustmanPlugin::PLUGIN_ID . '_removal_job';

    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return ilDustmanPlugin::PLUGIN_NAME . " Removal Job";
    }

    /**
     * @inheritDoc
     */
    public function getDescription() : string
    {
        return 'This cron job removes course- and group-objects according to the plugin configuration on the specified dates.';
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
        if ($this->isExecutionDay(new DateTime())) {
            $this->logger->info('[Dustman] Today some objects get deleted!');
            $this->deleteObjects();
        } else {
            $this->logger->info('[Dustman] Today is not a deletion day.');
        }

        return new ilDustmanSuccessResult();
    }

    protected function deleteObjects() : void
    {
        foreach ($this->getDeletableObjects() as $object) {
            try {
                $this->repository->deleteObject((int) $object['ref_id']);
            } catch (ilRepositoryException $e) {
                $this->logger->error("[Dustman] Failed to delete object '{$object['ref_id']}': {$e->getMessage()} {$e->getTraceAsString()}");
                continue;
            }
        }
    }

    /**
     * @return array
     */
    protected function getDeletableObjects() : array
    {
        $objects = $this->repository->getFilteredObjects(
            $this->getDeletableObjectTypes(),
            $this->config->getFilterKeywords(),
            $this->config->getFilterOlderThan() ?? 0
        );

        return $this->repository->filterObjectsWithinCategories(
            $objects,
            $this->config->getFilterCategories()
        );
    }

    /**
     * @return string[]
     */
    protected function getDeletableObjectTypes() : array
    {
        $types = [];
        if ($this->config->shouldDeleteCourses()) {
            $types[] = 'crs';
        }

        if ($this->config->shouldDeleteGroups()) {
            $types[] = 'grp';
        }

        return $types;
    }
}
