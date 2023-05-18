<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage contact
 */
class waContactDataTextStorage extends waContactDataStorage
{
    /**
     * @var waContactDataTextModel
     */
    protected $model;

    /**
     * Returns model
     *
     * @return waContactDataModel|waContactDataTextModel
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new waContactDataTextModel();
        }
        return $this->model;
    }

    protected function insertDataRows($contact, $data)
    {
        $insert = [];
        foreach ($data as $f => $f_rows) {
            foreach ($f_rows as $s => $row) {
                $insert[] = [
                    'contact_id' => $contact->getId(),
                    'field' => $f,
                    'ext' => $row['ext'],
                    'value' => $row['value'],
                    'sort' => (int)$s,
                ];
            }
        }

        $this->getModel()->multipleInsert($insert);
        return true;
    }
}

// EOF