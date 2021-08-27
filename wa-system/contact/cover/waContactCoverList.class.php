<?php

class waContactCoverList
{
    protected $id;

    /**
     * @var waContactFilesModel
     */
    protected $cfm;

    /**
     * @var waContactCoverThumbnailCreator
     */
    protected $tc;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param int $id
     * @param array $options
     *      string[] $options['sizes']
     *      string[] $options['size_aliases'] string to string mapping <size> => <name>
     *
     */
    public function __construct($id, array $options = [])
    {
        $this->id = $id;
        $this->cfm = new waContactFilesModel();

        $this->options = array_merge([
            'sizes' => ['100x100', '1408x440'],
            'size_aliases' => [],
        ], $options);

    }

    /**
     * @param int|int[]|null $photo_id
     * @return array|null $result
     *      for $photo_id = int[] or null:
     *          int     $result[<idx>]['id']
     *          string  $result[<idx>]['urls'][<size>]
     *      for $photo_id = int, $result is array OR null
     *          int     $result['id']
     *          string  $result['urls'][<size>]
     * @throws waException
     */
    public function getThumbnails($photo_id = null)
    {
        $sizes = $this->getSizes();
        if (!$sizes) {
            return [];
        }

        $is_scalar_input = is_scalar($photo_id);

        $condition = [];
        if ($photo_id !== null) {
            $photo_ids = waUtils::toIntArray($photo_id);
            $photo_ids = waUtils::dropNotPositive($photo_ids);
            if (!$photo_ids) {
                return [];
            }
            $condition['id'] = $photo_ids;
        }
        $condition['purpose'] = waContactFilesModel::PURPOSE_COVER;

        $records = $this->cfm->getByContact($this->id, $condition);
        if (!$records) {
            return [];
        }

        foreach ($records as &$record) {
            unset($record['purpose']);
        }
        unset($record);

        $contact_covers_dir = $this->getContactCoversDir($this->id);

        $cdn = $this->getCDN();
        if ($cdn) {
            $contact_covers_url = $cdn . wa()->getDataUrl($contact_covers_dir, true, 'contacts');
        } else {
            $contact_covers_url = wa()->getDataUrl($contact_covers_dir, true, 'contacts', true);
        }

        foreach ($records as &$record) {
            $record['urls'] = [];
            foreach ($sizes as $sz) {
                $url = $contact_covers_url . $record['id'] . '/' . $sz . '.jpg';
                $record['urls'][$sz] = $url;
            }
        }
        unset($record);

        $this->workup($records);

        if ($is_scalar_input) {
            return reset($records);
        }

        return $records;
    }

    public function sort(array $photo_ids)
    {
        $photo_ids = waUtils::dropNotPositive($photo_ids);
        $photo_ids = array_values($photo_ids);
        $sorts = array_flip($photo_ids);
        $max_sort = count($sorts) - 1;

        $records = $this->cfm->getByContact($this->id, [
            'purpose' => waContactFilesModel::PURPOSE_COVER
        ]);

        foreach ($records as $record) {
            $id = $record['id'];
            if (!isset($sorts[$id])) {
                $max_sort++;
                $sorts[$id] = $max_sort;
            }
            $this->cfm->updateById($id, ['sort' => $sorts[$id]]);
        }
    }

    protected function workup(array &$records)
    {
        $size_aliases = isset($this->options['size_aliases']) && is_array($this->options['size_aliases']) ? $this->options['size_aliases'] : [];

        foreach ($records as &$record) {
            $urls = isset($record['urls']) && is_array($record['urls']) ? $record['urls'] : [];

            $record['urls'] = [];
            foreach ($urls as $size => $url) {
                if (isset($size_aliases[$size])) {
                    $alias = $size_aliases[$size];
                    $size = $alias;
                }
                $record['urls'][$size] = $url;
            }
        }
        unset($record);
    }
    protected function getCDN()
    {
        // TODO: CDN
        return null;
    }

    /**
     * Add to the end of list
     * @param waRequestFile $file
     * @return int - photo id otherwise 0
     * @throws waException
     */
    public function add(waRequestFile $file)
    {
        $sizes = $this->getSizes();
        if (!$sizes) {
            return 0;
        }

        $photo_id = $this->addRecord($file->name);

        $contact_covers_dir = $this->getContactCoversDir($this->id);
        $contact_covers_path = wa()->getDataPath($contact_covers_dir, true, 'contacts');

        $original_photo_dir = $contact_covers_path . $photo_id . '/';
        $original_photo_name = 'original.' . $file->extension;

        $this->createFile($original_photo_dir, true);

        $original_photo_path = $original_photo_dir . $original_photo_name;
        $ok = $file->moveTo($original_photo_dir, $original_photo_name);
        if (!$ok) {
            $this->deleteRecord($photo_id);
            return 0;
        }

        foreach ($sizes as $sz) {
            $path = $contact_covers_path . $photo_id . '/' . $sz . '.jpg';
            $this->createThumbnail($original_photo_path, $path, $sz);
        }

        return $photo_id;
    }

    /**
     * @param int|int[] $photo_id
     * @throws waException
     */
    public function delete($photo_id)
    {
        $photo_ids = waUtils::toIntArray($photo_id);
        $photo_ids = waUtils::dropNotPositive($photo_ids);
        if (!$photo_ids) {
            return;
        }

        $this->deleteRecord($photo_ids);

        foreach ($photo_ids as $photo_id) {
            $contact_covers_dir = $this->getContactCoversDir($this->id);
            $contact_covers_path = wa()->getDataPath($contact_covers_dir, true, 'contacts');
            $path = $contact_covers_path . $photo_id . '/';
            $this->deleteFile($path);
        }
    }

    public function deleteAll()
    {
        $this->cfm->deleteByField([
            'contact_id' => $this->id,
            'purpose' => waContactFilesModel::PURPOSE_COVER
        ]);
        $contact_covers_dir = $this->getContactCoversDir($this->id);
        $contact_covers_path = wa()->getDataPath($contact_covers_dir, true, 'contacts');

        $this->deleteFile($contact_covers_path);
    }

    /**
     * @param $original_path
     * @param $path
     * @param $size
     * @return bool
     * @throws waException
     */
    protected function createThumbnail($original_path, $path, $size)
    {
        return $this->getThumbnailCreator()->create($original_path, $path, $size);
    }

    /**
     * @param string $name
     * @return int
     * @throws waException
     */
    protected function addRecord($name = null)
    {
        $data = [
            'purpose' => waContactFilesModel::PURPOSE_COVER
        ];
        if ($name !== null) {
            $data['name'] = $name;
        }
        return $this->cfm->add($this->id, $data);
    }

    protected function deleteRecord($photo_id)
    {
        $this->cfm->deleteByField([
            'id' => $photo_id,
            'contact_id' => $this->id,
            'purpose' => waContactFilesModel::PURPOSE_COVER,
        ]);
    }

    protected function deleteFile($path)
    {
        try {
            waFiles::delete($path);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    protected function createFile($path, $is_dir)
    {
        return waFiles::create($path, $is_dir);
    }

    protected function getSizes()
    {
        return $this->options['sizes'];
    }

    /**
     * @return waContactCoverThumbnailCreator
     */
    protected function getThumbnailCreator()
    {
        if (!$this->tc) {
            $this->tc = new waContactCoverThumbnailCreator();
        }
        return $this->tc;
    }

    protected function getContactCoversDir($contact_id)
    {
        $str = str_pad($contact_id, 4, '0', STR_PAD_LEFT);
        $str = substr($str, -2).'/'.substr($str, -4, 2);
        return "covers/{$str}/{$contact_id}/";
    }
}
