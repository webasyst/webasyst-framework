<?php
/**
 * Represents all known data of a specific instance of a block,
 * with all its children recursively.
 */
class siteBlockData
{
    public $block_type;
    public $db_row;
    public $data;
    public $files;
    public $children;

    public function __construct(siteBlockType $block_type, $block_db_row = null)
    {
        $this->block_type = $block_type;
        $this->children = [];
        $this->files = [];
        if ($block_db_row) {
            $this->setDbRow($block_db_row);
        }
    }

    public function getId()
    {
        return ifset($this->db_row, 'id', null);
    }

    public function getPageId()
    {
        return ifset($this->db_row, 'page_id', null);
    }

    public function getParentId()
    {
        return ifset($this->db_row, 'parent_id', null);
    }

    public function setDbRow(array $block_db_row)
    {
        $this->db_row = $block_db_row;
        $this->data = json_decode(ifset($block_db_row, 'data', '[]'), true);
        unset($this->data['additional']);
        return $this;
    }

    public function setFiles(array $files)
    {
        $this->files = [];
        foreach($files as $key => $file) {
            //$file['path'] = self::getBlockpageFilePath($this->files[$key]);
            $file['url'] = self::getBlockpageFileUrl($file);
            $this->files[$key] = $file;
        }
    }

    public function getFilePath($key)
    {
        if (!isset($this->files[$key])) {
            return '';
        }
        return self::getBlockpageFilePath($this->files[$key]);
    }

    public function getFileUrl($key)
    {
        if (!isset($this->files[$key])) {
            return '';
        }
        return self::getBlockpageFileUrl($this->files[$key]);
    }

    public static function getBlockpageFilePath($file)
    {
        return self::getBlockpageFileDirPath($file['id']).$file['name'];
    }

    public static function getBlockpageFileDirPath($id)
    {
        return wa()->getDataPath(self::getBlockpageFileDir($id), true, 'site');
    }

    public static function getBlockpageFileUrl($file)
    {
        return wa()->getDataUrl(self::getBlockpageFileDir($file['id']).$file['name'], true, 'site');
    }

    public static function getBlockpageFileDir($id)
    {
        $padded_id = str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        return sprintf('%s/%s/%s/', substr($padded_id, -4, 2), substr($padded_id, -2), $id);
    }

    public function addChildren(array $children)
    {
        foreach($children as $c => $n) {
            $this->addChild($n, $c);
        }
        return $this;
    }

    public function addChild(siteBlockData $block_data, $child_key=null)
    {
        $id = ifset($block_data->db_row, 'id', null);
        $child_key = ifset($block_data->db_row, 'child_key', ifset($child_key, ''));
        if (!$id) {
            $id = -1;
            while (isset($this->children[$child_key][$id])) {
                $id--;
            }
        }
        $this->children[$child_key][$id] = $block_data;
        uasort($this->children[$child_key], function($a, $b) {
            return ifset($a->db_row, 'sort', 0) - ifset($b->db_row, 'sort', 0);
        });
        return $this;
    }

    public function getRenderedChildren($is_backend, $tmpl_vars=[])
    {
        $result = [];
        foreach($this->children as $child_key => $arr) {
            foreach($arr as $child) {
                $result[$child_key][] = [
                    'data' => $child->ensureAdditionalData(),
                    'html' => $child->block_type->render($child, $is_backend, $tmpl_vars),
                ] + ifset($child->db_row, ['id' => '']);
            }
        }
        return $result;
    }

    public function ensureAdditionalData($force=false)
    {
        if ($force) {
            unset($this->data['additional']);
        }
        if (!isset($this->data['additional'])) {
            $additional_data = $this->block_type->additionalData($this);
            $this->data['additional'] = ifempty($additional_data, []);
        }
        return $this;
    }

    public function getDataEncoded()
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    }
}
