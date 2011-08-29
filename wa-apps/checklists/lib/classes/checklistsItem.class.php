<?php

class checklistsItem
{
    public static function prepareItems($items)
    {
        foreach($items as &$item) {
            $item = self::prepareItem($item);
        }
        return $items;
    }

    /** Add `when` and `who` fields (used in templated) to given item db row. */
    public static function prepareItem($item)
    {
        $item['name'] = htmlspecialchars($item['name']);
        $item['when'] = $item['done'] ? waDateTime::format('humandatetime', $item['done']) : '';
        $item['who'] = '';
        if ($item['contact_id'] && wa()->getUser()->getId() != $item['contact_id']) {
            $c = new waContact($item['contact_id']);
            try {
                $item['who'] = htmlspecialchars($c->getName());
            } catch (Exception $e) {}
        }
        return $item;
    }
}

