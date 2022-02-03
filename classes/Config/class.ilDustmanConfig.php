<?php

/**
 * ilDustmanConfig stores all plugin configurations.
 *
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 *
 * This class is used to store any sort of value for a specific CONFIGURATION_IDENTIFIER.
 * Since any type of value is accepted by the setValue(), the data will be encoded to
 * JSON and stored as TEXT in the database. Therefore values have to be type-casted in
 * most cases before used.
 *
 * setValue() and getValue() although distinguish arrays from other values to save
 * developers the trouble of exploding strings. Therefore getValue() will return both,
 * strings and arrays.
 *
 * - general usage:
 *
 *      - load configuration:
 *
 *          $config = ilDustmanConfig::get();
 *          $option = $config[ilDustmanConfig::<<CONFIGURATION_IDENTIFIER>>]->getValue();
 *          or
 *          $config = ilDustmanConfig::find(ilDustmanConfig::<<CONFIGURATION_IDENTIFIER>>);
 *          $option = $config->getValue();
 *
 *      - update configuration:
 *          $config = ilDustmanConfig::find(ilDustmanConfig::<<CONFIGURATION_IDENTIFIER>>);
 *          $config
 *              ->setValue(mixed $value)
 *              ->store();
 */
class ilDustmanConfig extends ActiveRecord
{
    /**
     * @var string table name for database representation.
     */
    public const TABLE_NAME = 'xdustman_config';

    /**
     * @var string primary key regex pattern
     */
    protected const IDENTIFIER_REGEX = '/^[A-Za-z0-9_-]*$/';

    /**
     * @var string identifier name
     */
    public const IDENTIFIER = 'config_key';

    /**
     * configuration identifiers
     */
    public const CNF_DELETE_GROUPS      = 'delete_groups';
    public const CNF_DELETE_COURSES     = 'delete_courses';
    public const CNF_DELETE_IN_DAYS     = 'delete_objects_in_days';
    public const CNF_REMINDER_IN_DAYS   = 'reminder_in_days';
    public const CNF_REMINDER_TITLE     = 'reminder_title';
    public const CNF_REMINDER_CONTENT   = 'reminder_content';
    public const CNF_REMINDER_EMAIL     = 'email';
    public const CNF_FILTER_CATEGORIES  = 'dont_delete_objects_in_category';
    public const CNF_FILTER_DATES       = 'checkdates';
    public const CNF_FILTER_KEYWORDS    = 'keywords';

    /**
     * @var string[] list of possible configurations.
     */
    public const CNF_IDENTIFIERS = [
        self::CNF_DELETE_GROUPS,
        self::CNF_DELETE_COURSES,
        self::CNF_DELETE_IN_DAYS,
        self::CNF_REMINDER_IN_DAYS,
        self::CNF_REMINDER_TITLE,
        self::CNF_REMINDER_CONTENT,
        self::CNF_REMINDER_EMAIL,
        self::CNF_FILTER_CATEGORIES,
        self::CNF_FILTER_DATES,
        self::CNF_FILTER_KEYWORDS,
    ];

    /**
     * @var string mysql datetime format
     */
    protected const MYSQL_DATETIME_FORMAT = 'Y-m-d';

    /**
     * @var string
     * @con_has_field   true
     * @con_is_unique   true
     * @con_is_primary  true
     * @con_is_notnull  true
     * @con_fieldtype   text
     * @con_length      128
     */
    protected $config_key;

    /**
     * @var string
     * @con_has_field   true
     * @con_is_notnull  false
     * @con_fieldtype   clob
     */
    protected $config_value;

    /**
     * @return string
     */
    public static function returnDbTableName() : string
    {
        return self::TABLE_NAME;
    }

    /**
     * @return string
     */
    public function getIdentifier() : string
    {
        return $this->config_key;
    }

    /**
     * @param string $config_key
     * @return ilDustmanConfig
     * @throws arException
     */
    public function setIdentifier(string $config_key) : ilDustmanConfig
    {
        $this->validateIdentifier($config_key);
        $this->config_key = $config_key;
        return $this;
    }

    /**
     * @param mixed $value
     * @return ilDustmanConfig
     */
    public function setValue($value)
    {
        switch ($this->getIdentifier()) {
            case self::CNF_DELETE_COURSES:
            case self::CNF_DELETE_GROUPS:
                $value = (bool) $value;
                break;

            case self::CNF_FILTER_DATES:
                $value = $this->transformFromDatetime($value);
                break;
        }

        $this->config_value = json_encode($value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $value = json_decode($this->config_value, true);
        switch ($this->getIdentifier()) {
            case self::CNF_FILTER_KEYWORDS:
            case self::CNF_FILTER_CATEGORIES:
                return (array) $value;

            case self::CNF_DELETE_COURSES:
            case self::CNF_DELETE_GROUPS:
                return (bool) $value;

            case self::CNF_FILTER_DATES:
                return $this->transformToDatetime($value);

            default:
                // remove quotes which come from json_decode() in strings.
                return trim($value, '"');
        }
    }

    /**
     * checks primary key value for prohibited characters.
     * @param string $identifier
     * @throws arException
     */
    protected function validateIdentifier(string $identifier) : void
    {
        if (!preg_match(self::IDENTIFIER_REGEX, $identifier)) {
            throw new arException(
                arException::UNKNONWN_EXCEPTION,
                'Prohibited characters in primary key value $identifier: ' . $identifier
            );
        }
    }

    /**
     * transforms a datetime object to a mysql compatible string.
     * @param DateTimeImmutable|null $datetime
     * @return string|null
     */
    protected function transformFromDatetime(?DateTimeImmutable $datetime) : ?string
    {
        if (null !== $datetime) {
            return $datetime->format(self::MYSQL_DATETIME_FORMAT);
        }

        return null;
    }

    /**
     * transforms a mysql datetime string into a php datetime object.
     *
     * @param string|null $value
     * @return DateTime|null
     */
    protected function transformToDatetime(?string $value) : ?DateTimeImmutable
    {
        if (!empty($value)) {
            $datetime = DateTimeImmutable::createFromFormat(self::MYSQL_DATETIME_FORMAT, $value);
            return ($datetime) ?: null;
        }

        return null;
    }
}