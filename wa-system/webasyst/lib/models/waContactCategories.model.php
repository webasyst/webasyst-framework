<?php

class waContactCategoriesModel extends waModel
{
    protected $table = 'wa_contact_categories';

    /**
     * @param int $id contact_id
     * @return array of categories, id => category row from wa_contact_categories.
     */
    public function getContactCategories($id)
    {
        $sql = "SELECT cat.*
                FROM wa_contact_categories AS ccat
                    JOIN wa_contact_category AS cat
                        ON cat.id=ccat.category_id
                WHERE ccat.contact_id=i:id";

        return $this->query($sql, array('id' => $id))->fetchAll('id');
    }

    /**
     * @param array $ids list of contact ids
     * @return array contact_id => list of category ids
     *
     * @todo add typehinting to parameter then fix many calls
     */
    public function getContactsCategories($ids)
    {
        $query = $this->select('contact_id, category_id')->where('contact_id IN (i:id)', array('id' => (array)$ids));
        $result = array_fill_keys($ids, array());
        foreach ($query->query() as $row) {
            $result[$row['contact_id']][] = $row['category_id'];
        }

        return $result;
    }

    /**
     * @param int $id contact_id
     * @param array $categories list of category ids
     */
    public function setContactCategories($id, array $categories)
    {
        $this->deleteByField('contact_id', $id);

        if (!$categories) {
            return;
        }

        $this->add($id, $categories);
    }

    /**
     * Remove each of given contacts from each of given categories
     *
     * @param int|array $contact_ids
     * @param int|array $category_ids
     */
    public function remove($contact_ids, $category_ids)
    {
        $contact_ids = (array)$contact_ids;
        $category_ids = (array)$category_ids;

        if (!$category_ids || !$contact_ids) {
            return;
        }

        $this->deleteByField(array('category_id' => $category_ids, 'contact_id' => $contact_ids));
    }

    /**
     * @param int|array $contact_id
     * @param int|array $category_id
     * @return array|null
     * @throws waException
     */
    public function inCategory($contact_id, $category_id)
    {
        return $this->getByField(array('contact_id' => $contact_id, 'category_id' => $category_id));
    }

    /**
     * Add each of given contacts to each of given categories
     *
     * @param int|array $contact_ids
     * @param int|array $category_ids
     */
    public function add($contact_ids, $category_ids)
    {
        $contact_ids = array_filter(array_map('intval', (array)$contact_ids));
        $category_ids = array_filter(array_map('intval', (array)$category_ids));

        if (!$category_ids || !$contact_ids) {
            return;
        }

        $values = array();
        foreach ($contact_ids as $id) {
            foreach ($category_ids as $cid) {
                $values[] = array('contact_id' => $id, 'category_id' => $cid);
            }
        }

        $this->multipleInsert($values);
    }
}
