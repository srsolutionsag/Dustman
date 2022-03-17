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
            SELECT ref.ref_id, obj.title FROM object_data AS obj
		        LEFT JOIN object_translation AS trans ON trans.obj_id = obj.obj_id
                LEFT JOIN object_reference AS ref ON ref.obj_id = obj.obj_id
		        WHERE obj.type = 'cat' and (obj.title LIKE $term OR trans.title LIKE $term);
		";

        $matches = [];
        foreach ($this->db->fetchAll($this->db->query($query)) as $entry) {
            $display_name = "{$entry['title']} ({$entry['ref_id']})";
            $matches[] = [
                'value' => $entry['ref_id'],
                'display' => $display_name,
                'searchBy' => $display_name,
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
        $type_string = $this->db->in('obj.type', $object_types, false, 'text');
        $key_string  = $this->db->in('il_meta_keyword.keyword', $keywords, false, 'text');

        $query = "
		    SELECT obj.obj_id, obj.title, ref.ref_id, obj.type FROM object_data AS obj
		        INNER JOIN object_reference AS ref 
		            ON ref.obj_id = obj.obj_id 
		            AND ref.deleted IS NULL
		        WHERE
				    $type_string
			    AND obj.create_date < DATE_SUB(NOW(), INTERVAL $age_in_days DAY)
			    AND NOT EXISTS (
					SELECT * FROM il_meta_keyword 
					WHERE il_meta_keyword.obj_id = obj.obj_id 
					AND $key_string
				);
		";

        $objects = [];
        foreach ($this->db->fetchAll($this->db->query($query)) as $entry) {
            $objects[] = $entry;
        }

        return $objects;
    }

    /**
     * Filters an array of objects and removes them if they are
     * contained in one of the given categories.
     * @param array<string, mixed> $objects
     * @param int[] $categories
     * @return array
     */
    public function filterObjectsWithinCategories(array $objects, array $categories) : array
    {
        $categories = $this->determineActualCategoryRefIds($categories);
        $result = [];

        foreach ($objects as $object) {
            $object_node_path = $this->tree->getPathId($object['ref_id']);
            $object_contained = false;

            foreach ($categories as $category) {
                if (in_array($category, $object_node_path, true)) {
                    $object_contained = true;
                    break;
                }
            }

            if (!$object_contained) {
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
     * @return ilDustmanConfigDTO
     */
    public function getConfig() : ilDustmanConfigDTO
    {
        $config = new ilDustmanConfigDTO();
        return $config
            ->setDeleteGroups($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_DELETE_GROUPS, false))
            ->setDeleteCourses($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_DELETE_COURSES, false))
            ->setReminderInterval($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_REMINDER_IN_DAYS, null))
            ->setReminderTitle($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_REMINDER_TITLE, ''))
            ->setReminderContent($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_REMINDER_CONTENT, ''))
            ->setReminderEmail($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_REMINDER_EMAIL, ''))
            ->setFilterKeywords($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_FILTER_KEYWORDS, []))
            ->setFilterCategories($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_FILTER_CATEGORIES, []))
            ->setFilterOlderThan($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_FILTER_OLDER_THAN, null))
            ->setExecDates($this->getConfigValueOrDefault(ilDustmanConfigAR::CNF_EXEC_ON_DATES, []));
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
     * @return ilDustmanConfigAR
     * @throws arException
     */
    public function getConfigByIdentifier(string $identifier) : ilDustmanConfigAR
    {
        /** @var $config ilDustmanConfigAR */
        $config = ilDustmanConfigAR::where([ilDustmanConfigAR::IDENTIFIER => $identifier], '=')->first();
        if (null === $config) {
            $config = new ilDustmanConfigAR();
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
        /** @var $config ilDustmanConfigAR */
        $config = ilDustmanConfigAR::find($identifier);
        if (null !== $config) {
            return $config->getValue();
        }

        return $default;
    }

    /**
     * for backwards compatibility this method finds obj_id's in an
     * array of ref_id's and pushes all category references of found
     * obj_id's to the given list of ids.
     * @param array $ids
     * @return array
     */
    protected function determineActualCategoryRefIds(array $ids) : array
    {
        $result = [];
        foreach ($ids as $id) {
            if (ilObject2::_exists($id, true, 'cat')) {
                $result[] = $id;
            } else {
                $references = ilObject2::_getAllReferences($id);
                foreach ($references as $ref_id) {
                    if ('cat' === ilObject2::_lookupType($ref_id, true)) {
                        $result[] = $ref_id;
                    }
                }
            }
        }

        return $result;
    }
}