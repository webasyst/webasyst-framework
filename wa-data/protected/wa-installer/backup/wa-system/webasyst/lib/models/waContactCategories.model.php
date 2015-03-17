<?php

class waContactCategoriesModel extends waModel
{
    protected $table = 'wa_contact_categories';
    
    /** @param int $id contact_id
      * @return array of categories, id => category row from wa_contact_categories. */
    public function getContactCategories($id) {
        $sql = "SELECT cat.*
                FROM wa_contact_categories AS ccat
                    JOIN wa_contact_category AS cat
                        ON cat.id=ccat.category_id
                WHERE ccat.contact_id=i:id";
        return $this->query($sql, array('id' => $id))->fetchAll('id');
    }

    /** @param array $ids list of contact ids
      * @return array contact_id => list of category ids */
    public function getContactsCategories($ids) {
        $sql = "SELECT contact_id, category_id
                FROM wa_contact_categories
                WHERE contact_id IN (i:ids)";
        $result = array_fill_keys($ids, array());
        foreach($this->query($sql, array('ids' => $ids)) as $row) {
            $result[$row['contact_id']][] = $row['category_id'];
        }
        return $result;
    }

    /** @param int $id contact_id
      * @param array $categories list of category ids */
    public function setContactCategories($id, $categories) {
        $sql = "DELETE FROM {$this->table} WHERE contact_id=i:id";
        $this->exec($sql, array('id' => $id));
        
        if (!$categories) {
            return;
        }
        
        $this->add($id, $categories);
    }
    
    /** Remove each of given contacts from each of given categories
      * @param int|array $contact_ids
      * @param int|array $category_ids */
    public function remove($contact_ids, $category_ids) {
        if (!is_array($contact_ids)) {
            $contact_ids = array($contact_ids);
        }
        if (!is_array($category_ids)) {
            $category_ids = array($category_ids);
        }
        
        if (!$category_ids || !$contact_ids) {
            return;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE contact_id IN (i:contacts) AND category_id IN (i:categories)";
        $this->exec($sql, array('contacts' => $contact_ids, 'categories' => $category_ids));
    }

    public function inCategory($contact_id, $category_id)
    {
        return $this->getByField(array('contact_id' => $contact_id, 'category_id' => $category_id));
    }
    
    /** Add each of given contacts to each of given cateroies.
      * @param int|array $contact_ids
      * @param int|array $category_ids
      */
    public function add($contact_ids, $category_ids) {
        if (!is_array($contact_ids)) {
            $contact_ids = array($contact_ids);
        }
        if (!is_array($category_ids)) {
            $category_ids = array($category_ids);
        }
        
        if (!$category_ids || !$contact_ids) {
            return;
        }
        
        $values = array();
        foreach($contact_ids as $id) {
            if (! ( $id = (int) $id)) {
                continue;
            }
            foreach($category_ids as $cid) {
                if (! ( $cid = (int) $cid)) {
                    continue;
                }
                $values[] = "($id,$cid)";
            }
        }
        
        if ($values) {
            $sql = "INSERT IGNORE INTO {$this->table} (contact_id, category_id) VALUES ".implode(',', $values);
            $this->exec($sql);
        }
    }
}

// EOF