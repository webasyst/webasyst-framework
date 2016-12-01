<?php

class teamWaLogModel extends waLogModel
{
    public function getPeriodByDate($options)
    {
        if (empty($options['start_date']) || empty($options['end_date'])) {
            return array();
        }

        $select = array(
            'app_id',
            'COUNT(*) AS `count`',
        );
        if ($options['group_by'] == 'months') {
            $select[] = "DATE_FORMAT(datetime, '%Y-%m-01') AS `date`";
        } else {
            $select[] = "DATE(datetime) AS `date`";
        }
        $select = join(',', $select);

        $field = array();
        foreach ($options as $key => $value) {
            if ($this->fieldExists($key)) {
                $field[$key] = $value;
            }
        }

        $where = array();
        if ($field) {
            $where[] = $this->getWhereByField($field);
        }
        $where[] = 'datetime >= ? AND datetime <= ?';
        $where = join(' AND ', $where);

        $sql = "SELECT {$select}
                FROM `wa_log` 
                WHERE {$where}
                GROUP BY `date`, app_id";
        $rows = $this->query($sql, array(
            $options['start_date'],
            $options['end_date'],
        ));

        $result = array();
        foreach ($rows as $row) {
            $result[$row['date']][$row['app_id']] = (int) $row['count'];
        }
        return $result;
    }

    public function getMinDate()
    {
        return $this->select('DATE(MIN(datetime))')->where("datetime != '0000-00-00'")->fetchField();
    }
}
