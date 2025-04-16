<?php
/**
 * Possible `status` values:
 * - 'final_published': public page visible @ frontend. `final_page_id` is NULL.
 * - 'final_unpublished': initial draft of the page, not visible @ frontend. `final_page_id` is NULL.
 * - 'draft': non-public version of a `final_published` page during editing. `final_page_id` contains id of the public page.
 */
class siteBlockpageModel extends waModel
{
    protected $table = 'site_blockpage';

    /**
     * @return int
     */
    public function createEmptyUnpublishedPage($domain_id)
    {
        $dt = date('Y-m-d H:i:s');
        $url = siteHelper::getIncrementUrl();
        return $this->insert([
            'domain_id' => $domain_id,
            'full_url' => $url,
            'url' => $url,
            'status' => 'final_unpublished',
            'create_datetime' => $dt,
            'update_datetime' => $dt,
        ]);
    }

    /**
     * @return int
     */
    public function createUnpublishedPage($domain_id, $page)
    {
        $dt = date('Y-m-d H:i:s');

        return $this->insert([
            'domain_id' => $domain_id,
            'name' => $page['name'],
            'title' => $page['title'],
            'url' => $page['url'],
            'full_url' => $page['url'],
            'theme' => $page['theme'],
            'parent_id' => ifset($page, 'parent_id', null),
            'status' => 'final_unpublished',
            'create_datetime' => $dt,
            'update_datetime' => $dt,
        ]);
    }

    public function updateByDomainId(int $domain_id, int $id, array $data)
    {
        return $this->updateByField([
            'domain_id' => $domain_id,
            'id' => $id,
        ], [
            'update_datetime' => date('Y-m-d H:i:s')
        ] + $data);
    }

    public function getByDomain($domain_id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE domain_id=? AND status<>'draft' ORDER BY `sort`";
        $pages = $this->query($sql, [$domain_id])->fetchAll('id');
        return $pages;
    }

    public function getByDomainWithVerifyDraftModifications($domain_id)
    {
        $sql = "SELECT t1.*,(t2.create_datetime != t2.update_datetime) as draft_changed FROM {$this->table} as t1 LEFT JOIN {$this->table} as t2 ON t1.id = t2.final_page_id WHERE t1.domain_id=? AND t1.status<>'draft' ORDER BY t1.sort";
        $pages = $this->query($sql, [$domain_id])->fetchAll('id');
        return $pages;
    }

    public function getByUrl(int $domain_id, string $url, $status='final_published')
    {
        $url = ltrim($url, '/');
        $url2 = rtrim($url, '/');
        $sql = "SELECT *
                FROM {$this->table}
                WHERE full_url IN (?)
                    AND status IN (?)
                    AND domain_id = ?
                ORDER BY id
                LIMIT 1";
        return $this->query($sql, [[$url, $url2], $status, $domain_id])->fetchAssoc();
    }

    public function getByUrlStartingWith(int $domain_id, string $url)
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE full_url LIKE ?
                    AND domain_id = ?
                ORDER BY id";
        return $this->query($sql, [$url.'%', $domain_id])->fetchAll('id');
    }

    public function delete($ids, $with_connected_pages=true)
    {
        if (!$ids) {
            return;
        }
        $ids = (array) $ids;
        if ($with_connected_pages) {
            $draft_ids = array_keys($this->select('id')->where('final_page_id IN (?)', [$ids])->fetchAll('id'));
            $published_ids = array_keys($this->select('final_page_id')->where('id IN (?) AND final_page_id IS NOT NULL', [$ids])->fetchAll('final_page_id'));
            $children_ids = $this->getChildIds(array_merge($ids, $published_ids));
            $ids = array_merge($ids, $draft_ids, $published_ids, $children_ids);
        }

        $block_ids = array_keys($this->query("SELECT id FROM site_blockpage_blocks WHERE page_id IN (?)", [$ids])->fetchAll('id'));
        if ($block_ids) {
            $blockpage_blocks_model = new siteBlockpageBlocksModel();
            $blockpage_blocks_model->delete($block_ids);
        }

        $this->deleteById($ids);

        $blockpage_params_model = new siteBlockpageParamsModel();
        $blockpage_params_model->deleteByField('page_id', $ids);

        if (!$with_connected_pages) {
            // fix children of deleted pages by moving them to root
            $child_pages = $this->getByField([
                'parent_id' => $ids,
            ], true);
            foreach ($child_pages as $p) {
                $this->move($p['id'], [
                    'domain_id' => $p['domain_id'],
                ]);
            }
        }
    }

    public function move($id, $parent_id, $before_id = null)
    {
        if ($id) {
            $page = $this->getById($id);
        }
        if (empty($page)) {
            return false;
        }

        if (!empty($before_id)) {
            $before = $this->getById($before_id);
            if ($before['parent_id']) {
                $parent_id = $before['parent_id'];
            } else {
                $parent_id = [
                    'domain_id' => $before['domain_id'],
                ];
            }
        } else {
            $before_id = null;
            $before = null;
        }

        if (is_numeric($parent_id)) {
            $parent = $this->getById($parent_id);
        } else {
            $parent = $parent_id;
            $parent_id = null;
        }

        $data = [];
        $search_fields = [
            'domain_id' => $parent['domain_id'],
            'parent_id' => $parent_id,
        ];
        foreach($search_fields as $k => $v) {
            if ($v !== $page[$k]) {
                $data[$k] = $v;
            }
        }
        if (array_key_exists('parent_id', $data)) {
            if ($parent_id && isset($parent['full_url'])) {
                $data['full_url'] = rtrim($parent['full_url'], '/').'/'.$page['url'];
            } else {
                $data['full_url'] = $page['url'];
            }
        }

        if ($before) {
            $data['sort'] = $before['sort'];
            $sql = "UPDATE {$this->table}
                    SET sort = sort + 1
                    WHERE ".$this->getWhereByField($search_fields)."
                        AND sort >= ?";
            $this->exec($sql, [$before['sort']]);
        } else {
            $sql = "SELECT MAX(sort)
                    FROM ".$this->table."
                    WHERE ".$this->getWhereByField($search_fields);
            $data['sort'] = (int)$this->query($sql)->fetchField() + 1;
        }

        if (!$this->updateById($id, $data)) {
            return false;
        }

        if (isset($data['domain_id']) || array_key_exists('parent_id', $data)) {
            $child_ids = $this->getChildIds($id);
            if ($child_ids) {
                if (array_key_exists('parent_id', $data)) {
                    $this->updateFullUrl($child_ids, $data['full_url'], $page['full_url']);
                    if (isset($data['full_url'])) {
                        $this->updateByField([
                            'final_page_id' => $id,
                        ], [
                            'full_url' => $data['full_url'],
                            'parent_id' => $data['parent_id'],
                        ]);
                    }
                }
                $update = array();
                if (isset($data['domain_id'])) {
                    $update['domain_id'] = $data['domain_id'];
                }
                if ($update) {
                    $this->updateById($child_ids, $update);
                }
            }
        }
        $data['id'] = $id;
        if (isset($data['full_url'])) {
            $data['old_full_url'] = $page['full_url'];
        }
        return $data;
    }

    public function getChildIds($id)
    {
        $result = [];
        $ids = array_fill_keys((array)$id, 1);
        $sql = "SELECT id FROM ".$this->table."
                WHERE (parent_id IN (:ids) AND status IN ('final_published', 'final_unpublished'))
                    OR (final_page_id IN (:ids) AND status='draft')";
        while ( ( $ids = $this->query($sql, ['ids' => array_keys($ids)])->fetchAll('id', true))) {
            $result += $ids;
        }
        return array_keys($result);
    }

    public function updateFullUrl($ids, $new_url, $old_url)
    {
        if ($new_url && substr($new_url, -1, 1) != '/') {
            $new_url .= '/';
        }
        if ($old_url && substr($old_url, -1, 1) != '/') {
            $old_url .= '/';
        }
        $sql = "UPDATE {$this->table}
                SET full_url = CONCAT(s:url, SUBSTR(full_url, ".(mb_strlen($old_url) + 1) ."))
                WHERE id IN (i:ids)
                    AND s:old_url = SUBSTR(full_url, 1, ".mb_strlen($old_url).")";
        return $this->exec($sql, [
            'ids' => $ids,
            'url' => $new_url,
            'old_url' => $old_url,
        ]);
    }

    public function cleanupPage($page_id)
    {
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $delete_blocks = $blockpage_blocks_model->select('id,page_id')->where('page_id=?', [$page_id])->fetchAll('id');
        $this->exec("DELETE FROM site_blockpage_params WHERE page_id=?", [$page_id]);
        if ($delete_blocks) {
            $blockpage_blocks_model->deleteByField('page_id', $page_id);
            $this->exec("DELETE FROM site_blockpage_block_files WHERE block_id IN (?)", [array_keys($delete_blocks)]);
        }
    }

    public function copyContents($source_page_id, $dest_page_id)
    {
        $unprocessed_ids = [];
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $blocks = $blockpage_blocks_model->getByPage($source_page_id);
        foreach ($blocks as $id => $b) {
            $unprocessed_ids[$id] = $id;
        }

        // Copy blocks and block files
        $sql = "INSERT INTO site_blockpage_block_files (file_id, block_id, `key`)
                SELECT file_id, ?, `key` FROM site_blockpage_block_files WHERE block_id=?";
        $old_id_to_new_id = [];
        $something_changed = true;
        while ($something_changed) {
            $something_changed = false;
            foreach ($unprocessed_ids as $id) {
                $b = $blocks[$id];
                if (empty($b['parent_id']) || isset($old_id_to_new_id[$b['parent_id']])) {
                    unset($b['id']);
                    $b['page_id'] = $dest_page_id;
                    if ($b['parent_id']) {
                        $b['parent_id'] = $old_id_to_new_id[$b['parent_id']];
                    }
                    $new_block_id = $blockpage_blocks_model->insert($b);
                    $this->exec($sql, [$new_block_id, $id]);

                    $old_id_to_new_id[$id] = $new_block_id;
                    unset($unprocessed_ids[$id]);
                    $something_changed = true;
                }
            }
        }

        // Page params
        $sql = "INSERT INTO site_blockpage_params (page_id, name, value)
                SELECT ?, name, value FROM site_blockpage_params
                WHERE page_id=?";
        $this->exec($sql, [$dest_page_id, $source_page_id]);
    }

    public function getDraftById($published_page_id)
    {
        return $this->query("
            SELECT *
            FROM {$this->table}
            WHERE final_page_id=?
                AND status='draft'
            LIMIT 1",
            [$published_page_id]
        )->fetchAssoc();
    }
}
