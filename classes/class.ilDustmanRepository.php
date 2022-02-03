<?php declare(strict_types=1);

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanRepository
{
    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     * @param ilDBInterface $db
     */
    public function __construct(ilDBInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $term
     * @return array|null
     */
    public function getCategoriesByTerm(string $term) : ?array
    {
        $term  = htmlspecialchars($term);
        $term  = $this->db->quote("%$term%", 'text');
        $query = "
            SELECT obj.obj_id, obj.title FROM object_data AS obj
		        LEFT JOIN object_translation AS trans ON trans.obj_id = obj.obj_id
		        WHERE obj.type = 'cat' and (obj.title LIKE $term OR trans.title LIKE $term);
		";

        $matches = [];
        foreach ($this->db->fetchAll($this->db->query($query)) as $entry) {
            $matches[] = [
                'value' => $entry['obj_id'],
                'display' => $entry['title'],
                'searchBy' => $entry['title'],
            ];
        }

        return (!empty($matches)) ? $matches : null;
    }

    /**
     * @param string $identifier
     * @param string $format
     * @return string
     */
    public function getDateTimeConfig(string $identifier, string $format) : string
    {
        /** @var $config_value DateTime|null */
        $config_value = $this->getConfigValueOrDefault($identifier, null);
        return (null != $config_value) ? $config_value->format($format) : '';
    }

    /**
     * @param string $identifier
     * @return ilDustmanConfig
     * @throws arException
     */
    public function getConfigByIdentifier(string $identifier) : ilDustmanConfig
    {
        /** @var $config ilDustmanConfig */
        $config = ilDustmanConfig::where([ilDustmanConfig::IDENTIFIER => $identifier], '=')->first();
        if (null === $config) {
            $config = new ilDustmanConfig();
            $config->setIdentifier($identifier);
        }

        return $config;
    }

    /**
     * @param string $identifier
     * @param mixed  $default
     * @return mixed
     */
    public function getConfigValueOrDefault(string $identifier, $default)
    {
        /** @var $config ilDustmanConfig */
        $config = ilDustmanConfig::find($identifier);
        if (null !== $config) {
            return $config->getValue();
        }

        return $default;
    }
}