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
     * @var ilTree
     */
    protected $tree;

    /**
     * @param ilDBInterface $db
     * @param ilTree        $tree
     */
    public function __construct(ilDBInterface $db, ilTree $tree)
    {
        $this->db = $db;
        $this->tree = $tree;
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
     * @param array $object_types
     * @param array $keywords
     * @param int   $age_in_days
     * @return array
     */
    public function getFilteredObjects(
        array $object_types,
        array $keywords,
        int $age_in_days
    ) : array {
        $day_string  = $this->db->quote($age_in_days, 'integer');
        $type_string = $this->db->in('obj.type', $object_types, false, 'text');
        $key_string  = $this->db->in('il_meta_keyword.keyword', $keywords, false, 'text');

        $query = "
		    SELECT obj.obj_id, obj.title, ref.ref_id, obj.type FROM object_data obj
		        INNER JOIN object_reference ref ON ref.obj_id = obj.obj_id AND ref.deleted IS NULL
		        WHERE
				    $type_string
			    AND obj.create_date < DATE_SUB(NOW(), INTERVAL $day_string DAY)
			    AND NOT EXISTS (
					SELECT * FROM il_meta_keyword WHERE il_meta_keyword.obj_id = obj.obj_id AND $key_string
				)
            ;
		";

        $result = [];
        foreach ($this->db->fetchAssoc($this->db->query($query)) as $entry) {
            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Filters an array of objects and removes them if they
     * @param array<string, mixed> $objects
     * @param int[] $categories
     * @return array
     */
    public function filterObjectsWithinCategories(array $objects, array $categories) : array
    {
        $result = [];
        foreach ($objects as $object) {
            $node_path = $this->tree->getNodePath($object['ref_id']);
            $contained = false;
            foreach ($categories as $category) {
                if (!in_array($category['obj_id'], $node_path, true)) {
                    $contained = true;
                    break;
                }
            }

            if (!$contained) {
                $result[] = $object;
            }
        }

        return $result;
    }

    /**
     * @param int $object_ref_id
     * @return int[]
     */
    public function getAdminsByObjectRefId(int $object_ref_id) : array
    {
        return ilParticipants::getInstance($object_ref_id)->getAdmins();
    }

    /**
     * @param array<string, mixed> $objects
     * @return void
     * @throws ilRepositoryException
     */
    public function deleteObjects(array $objects) : void
    {
        $object_ref_ids = [];
        foreach ($objects as $object) {
            $object_ref_ids[] = $object['ref_id'];
        }

        ilRepUtil::deleteObjects(null, $object_ref_ids);
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