<?php

class webasystSettingsDatabaseListAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $this->view->assign(array(
            'tables' => $this->getTables(),
        ));
    }

    protected function getTables()
    {
        $model = new waModel();
        $tables = $model->query('SHOW TABLE STATUS')->fetchAll('Name');
        $field_count = 0;
        foreach ($tables as &$table) {
            $table['is_mb4'] = preg_match('~^(utf8mb4_)~ui', $table['Collation']) ? true : false;
            $table['columns'] = $model->query('SHOW FULL COLUMNS FROM `'.$table['Name'].'`')->fetchAll('Field');
            $table['indexes'] = $model->query('SHOW INDEX FROM `'.$table['Name'].'`')->fetchAll('Column_name');
            $table['is_convertable'] = !empty($table['Collation']);

            $field_count += count($table['columns']);

            foreach ($table['columns'] as &$column) {
                $column['is_mb4'] = preg_match('~^(utf8mb4_)~ui', $column['Collation']) ? true : false;
                $column['is_index'] = isset($table['indexes'][$column['Field']]);
                $column['is_convertable'] = !empty($column['Collation']) && !$column['is_index'];
                if (!empty($column['Collation']) && !$column['is_mb4'] && $column['is_index']) {
                    $table['is_convertable'] = false;
                }
            }
            unset($column);
        }
        unset($table);

        return $tables;
    }
}