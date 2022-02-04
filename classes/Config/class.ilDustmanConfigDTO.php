<?php declare(strict_types=1);

/* Copyright (c) 2022 Thibeau Fuhrer <thibeau@sr.solutions> Extended GPL, see docs/LICENSE */

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanConfigDTO
{
    /**
     * @var string[]
     */
    protected $exec_dates = [];
    /**
     * @var bool
     */
    protected $delete_groups = false;
    /**
     * @var bool
     */
    protected $delete_courses = false;
    /**
     * @var int|null
     */
    protected $reminder_interval = null;
    /**
     * @var string|null
     */
    protected $reminder_title = '';
    /**
     * @var string|null
     */
    protected $reminder_content = '';
    /**
     * @var string|null
     */
    protected $reminder_email = '';
    /**
     * @var int|null
     */
    protected $filter_older_than = null;
    /**
     * @var string[]
     */
    protected $filter_keywords = [];
    /**
     * @var int[]
     */
    protected $filter_categories = [];

    /**
     * @return string[]
     */
    public function getExecDates() : array
    {
        return $this->exec_dates;
    }

    /**
     * @param string[] $exec_dates
     * @return ilDustmanConfigDTO
     */
    public function setExecDates(array $exec_dates) : ilDustmanConfigDTO
    {
        $this->exec_dates = $exec_dates;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldDeleteGroups() : bool
    {
        return $this->delete_groups;
    }

    /**
     * @param bool $delete_groups
     * @return ilDustmanConfigDTO
     */
    public function setDeleteGroups(bool $delete_groups) : ilDustmanConfigDTO
    {
        $this->delete_groups = $delete_groups;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldDeleteCourses() : bool
    {
        return $this->delete_courses;
    }

    /**
     * @param bool $delete_courses
     * @return ilDustmanConfigDTO
     */
    public function setDeleteCourses(bool $delete_courses) : ilDustmanConfigDTO
    {
        $this->delete_courses = $delete_courses;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getReminderInterval() : ?int
    {
        return $this->reminder_interval;
    }

    /**
     * @param int|null $reminder_interval
     * @return ilDustmanConfigDTO
     */
    public function setReminderInterval(?int $reminder_interval) : ilDustmanConfigDTO
    {
        $this->reminder_interval = $reminder_interval;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReminderTitle() : ?string
    {
        return $this->reminder_title;
    }

    /**
     * @param string|null $reminder_title
     * @return ilDustmanConfigDTO
     */
    public function setReminderTitle(?string $reminder_title) : ilDustmanConfigDTO
    {
        $this->reminder_title = $reminder_title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReminderContent() : ?string
    {
        return $this->reminder_content;
    }

    /**
     * @param string|null $reminder_content
     * @return ilDustmanConfigDTO
     */
    public function setReminderContent(?string $reminder_content) : ilDustmanConfigDTO
    {
        $this->reminder_content = $reminder_content;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReminderEmail() : ?string
    {
        return $this->reminder_email;
    }

    /**
     * @param string|null $reminder_email
     * @return ilDustmanConfigDTO
     */
    public function setReminderEmail(?string $reminder_email) : ilDustmanConfigDTO
    {
        $this->reminder_email = $reminder_email;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFilterOlderThan() : ?int
    {
        return $this->filter_older_than;
    }

    /**
     * @param int|null $filter_older_than
     * @return ilDustmanConfigDTO
     */
    public function setFilterOlderThan(?int $filter_older_than) : ilDustmanConfigDTO
    {
        $this->filter_older_than = $filter_older_than;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getFilterKeywords() : array
    {
        return $this->filter_keywords;
    }

    /**
     * @param string[] $filter_keywords
     * @return ilDustmanConfigDTO
     */
    public function setFilterKeywords(array $filter_keywords) : ilDustmanConfigDTO
    {
        $this->filter_keywords = $filter_keywords;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getFilterCategories() : array
    {
        return $this->filter_categories;
    }

    /**
     * @param int[] $filter_categories
     * @return ilDustmanConfigDTO
     */
    public function setFilterCategories(array $filter_categories) : ilDustmanConfigDTO
    {
        $this->filter_categories = $filter_categories;
        return $this;
    }
}