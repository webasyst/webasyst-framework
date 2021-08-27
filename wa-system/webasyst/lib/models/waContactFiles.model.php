<?php

class waContactFilesModel extends waModel
{
    protected $table = 'wa_contact_files';
    protected $id = 'id';

    const PURPOSE_COVER = 'cover';
    const PURPOSE_GENERAL = 'general';

    /**
     * @param int|int[] $contact_id
     * @param array $condition
     * @return array
     * @throws waException
     */
    public function getByContact($contact_id, array $condition = [])
    {
        $contact_ids = waUtils::toIntArray($contact_id);
        $contact_ids = waUtils::dropNotPositive($contact_ids);
        if (!$contact_ids) {
            return [];
        }

        $where = [
            'contact_id IN(:ids)'
        ];
        $bind_params = [
            'ids' => $contact_ids
        ];

        if ($condition) {
            $where[] = $this->getWhereByField($condition);
        }

        $iterator = $this->select('*')->order('contact_id, sort')
            ->where(join(' AND ', $where), $bind_params)->query();

        $result = array_fill_keys($contact_ids, []);
        foreach ($iterator as $item) {
            $c_id = $item['contact_id'];
            $result[$c_id][] = $item;
        }

        if (is_scalar($contact_id)) {
            return $result[$contact_id];
        }

        return $result;
    }

    /**
     * @param int|int[] $contact_id
     */
    public function deleteByContact($contact_id)
    {
        $this->deleteByField('contact_id', $contact_id);
    }

    /**
     * @param $contact_id
     * @param array $data
     *      string $data['name'] [optional]
     *      string $data['purpose'] [optional]
     * @return int - ID of new record
     * @throws waException
     */
    public function add($contact_id, array $data = [])
    {
        unset($data['id'], $data['contact_id'], $data['sort']);

        $ids = $this->addBunch($contact_id, [
            $data
        ]);

        if (!$ids) {
            return 0;
        }

        return reset($ids);
    }

    /**
     * @param int $contact_id
     * @param array[]array $data - list of $record
     *      $record['name'] [optional]
     * @return int[] - list of photo IDs
     *
     * @throws waException
     */
    public function addBunch($contact_id, array $data)
    {
        $current_max_sort = $this->select('MAX(sort)')
            ->where('contact_id = ?', $contact_id)->query()->fetchField();

        $current_max_sort = wa_is_int($current_max_sort) ? intval($current_max_sort) : -1;

        $ids = [];

        $i = 0;
        foreach ($data as $item) {
            unset($item['id'], $item['sort'], $item['contact_id']);

            $insert = array_merge($item, [
                'contact_id' => $contact_id,
                'sort' => $current_max_sort + $i + 1,
            ]);
            $i++;

            $id = $this->insert($insert);
            if ($id) {
                $ids[] = $id;
            }
        }

        return waUtils::toIntArray($ids);
    }
}
