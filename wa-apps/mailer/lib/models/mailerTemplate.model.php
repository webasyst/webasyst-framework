<?php

/**
 * Storage for campaign templates.
 * Templates are kept in the same table as regilar campaings to avoid several
 * DB tables with identical structure. Templates differ from campaigns by `is_template` flag.
 */
class mailerTemplateModel extends mailerMessageModel
{
    public function getTemplates()
    {
        $sql = "SELECT m.id, m.name, m.subject, m.body, mp.value AS description, CAST(mps.value AS SIGNED) AS sort
                FROM ".$this->table." AS m
                    LEFT JOIN mailer_message_params AS mp
                        ON mp.message_id=m.id
                            AND mp.name='description'
                    LEFT JOIN mailer_message_params AS mps
                        ON mps.message_id=m.id
                            AND mps.name='sort'
                WHERE is_template = 1
                ORDER BY sort, id DESC";
        return $this->query($sql)->fetchAll();
    }

    public function updateSort($values)
    {
        $vals = array();
        foreach($values as $id => $sort) {
            $vals[] = "(".((int)$id).", 'sort', ".((int)$sort).")";
        }
        $vals = implode(',', $vals);
        $sql = "INSERT INTO mailer_message_params (message_id, name, value)
                VALUES {$vals}
                ON DUPLICATE KEY UPDATE value=VALUES(value)";
        $this->query($sql);
    }

    public function getById($id)
    {
        $t = parent::getById($id);
        if ($t && !$t['is_template']) {
            return null;
        }
        return $t;
    }

    public function getEmptyTemplate()
    {
        $t = $this->getMetadata();
        foreach($t as &$v) {
            $v = '';
        }

        $t['is_template'] = 1;
        //$t['body'] = '<a href="{$unsubscribe_link}">'._w('Unsubscribe').'</a>';
        return $t;
    }

    public function countAll()
    {
        return $this->select('count(*)')->where('is_template=1')->fetchField();
    }
}

