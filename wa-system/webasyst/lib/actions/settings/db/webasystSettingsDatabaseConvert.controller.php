<?php

class webasystSettingsDatabaseConvertController extends waJsonController
{
    const CHARSET = 'utf8mb4';
    const COLLATION = 'utf8mb4_general_ci';

    /**
     * @var waModel
     */
    protected $model;

    /**
     * @var int
     */
    protected $process_hash;

    /**
     * @var null|string
     */
    protected $log_path;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var null|string
     */
    protected $column;

    public function execute()
    {
        $this->process_hash = waRequest::post('process_hash', null, waRequest::TYPE_INT);
        $this->table = waRequest::post('table', null, waRequest::TYPE_STRING_TRIM);
        $this->column = waRequest::post('column', null, waRequest::TYPE_STRING_TRIM);

        if (empty($this->table)) {
            return $this->errors = _w('Table not found');
        }

        $this->convert();

        $this->response = self::COLLATION;
    }

    protected function convert()
    {
        $is_column = $this->isColumn();

        try {
            ($is_column) ? $this->convertColumn() : $this->convertTable();
        } catch (waException $e) {
            $message = array(
                'table'  => $this->table,
                'column' => $this->column,
                'error'  => $e->getMessage(),
            );
            $this->addLog($message);
            $this->errors = array(
                'error'    => $e->getMessage(),
                'log_path' => $this->getLogPath(),
            );
        }
    }

    protected function convertTable()
    {
        $table_name = $this->getModel()->escape($this->table);
        $sql = "ALTER TABLE `{$table_name}` CONVERT TO CHARACTER SET ".self::CHARSET." COLLATE ".self::COLLATION;
        $this->getModel()->exec($sql);
    }

    /**
     * @throws waException
     */
    protected function convertColumn()
    {
        $table_name = $this->getModel()->escape($this->table);
        $table_columns = $this->getModel()->query('SHOW FULL COLUMNS FROM `'.$table_name.'`')->fetchAll('Field');

        $column_data = ifempty($table_columns, $this->column, null);
        if (empty($column_data)) {
            throw new waException(_w('Column not found'));
        }

        if (empty($column_data['Collation'])) {
            throw new waException(_w('The column is not a string'));
        }

        if ($column_data['Collation'] == self::COLLATION) {
            return;
        }

        $field = $column_data['Field'];
        $type = $column_data['Type'];

        $null = 'NULL';
        if (strtoupper($column_data['Null']) === 'NO') {
            $null = 'NOT NULL';
        }

        $default = $column_data['Default'];
        if ($default !== null) {
            if ($default == 'CURRENT_TIMESTAMP') {
                $default = "DEFAULT ".$default;
            } else {
                $default = "DEFAULT '{$this->getModel()->escape($default)}'";
            }
        }

        $sql = "ALTER TABLE `{$this->table}` MODIFY `{$field}` {$type} CHARACTER SET ".self::CHARSET." COLLATE ".self::COLLATION." {$null} {$default}";

        $this->getModel()->exec($sql);
    }

    protected function isColumn()
    {
        return !empty($this->column);
    }

    protected function addLog($message)
    {
        if (!is_scalar($message)) {
            $message = var_export($message, true);
        }

        $file = $this->getLogPath();

        waLog::log($message, $file);
    }

    protected function getLogPath()
    {
        if ($this->log_path === null) {
            $file = '/db/mysql_mb4_convert/';
            $file .= !empty($this->process_hash) ? $this->process_hash.'_error.log' : 'convert_error.log';
            $this->log_path = $file;
        }
        return $this->log_path;
    }

    protected function getModel()
    {
        if (!$this->model) {
            $this->model = new waModel();
        }

        return $this->model;
    }
}