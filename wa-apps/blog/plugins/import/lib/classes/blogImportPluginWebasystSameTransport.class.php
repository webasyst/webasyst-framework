<?php


class blogImportPluginWebasystSameTransport extends blogImportPluginWebasystTransport
{
    protected $sql_options;

    /**
     * @var waModel
     */
    protected $source;
    protected $source_path;
    protected $dbkey;

    protected function initOptions()
    {
        if (!extension_loaded('simplexml')) {
            throw new waException(_wp('PHP extension SimpleXML required'));
        }
        $this->addOption('path', array(
            'title'        => _wp('Path to folder'),
            'value'        => wa()->getConfig()->getRootPath(),
            'description'  => _wp('Path to folder of the WebAsyst (old version) installation'),
            'control_type' => waHtmlControl::INPUT,
        ));
    }

    protected function waQuery($sql, $limit = true)
    {
        $sql = 'SELECT '.$sql;
        $data = null;
        try {
            $result = $this->getSourceModel()->query($sql);
            /**
             * @var waDbResultSelect $result
             */
            $this->log(__METHOD__.var_export(compact('sql', 'result'), true), self::LOG_DEBUG);
            $data = $limit ? $result->fetchAssoc() : $result->fetchAll();

            $this->log(__METHOD__.var_export(compact('sql', 'data'), true), self::LOG_DEBUG);
        } catch (waException $ex) {
            $this->log($ex);
        }
        return $data;
    }


    public function validate($result, &$errors)
    {
        try {
            $this->getSourceModel();
            $this->addOption('path', array('readonly' => true));
        } catch (waException $ex) {
            $result = false;
            $errors['path'] = $ex->getMessage();
            $this->addOption('path', array('readonly' => false));
        }
        return parent::validate($result, $errors);
    }

    /**
     * @return waModel
     * @throws waException
     */
    private function getSourceModel()
    {
        if (!$this->source) {
            $this->source_path = $this->option('path');
            if (substr($this->source_path, -1) != '/') {
                $this->source_path .= '/';
            }
            if (!file_exists($this->source_path)) {
                throw new waException(sprintf(_wp('Invalid PATH %s; %s'), $this->source_path, _wp('directory not exists')));
            }
            if (!file_exists($this->source_path.'kernel/wbs.xml')) {
                throw new waException(sprintf(_wp('Invalid PATH %s; %s'), $this->source_path, _wp('file kernel/wbs.xml not found')));
            }

            /**
             *
             * @var SimpleXMLElement $wbs
             */
            $wbs = simplexml_load_file($this->source_path.'kernel/wbs.xml');
            $this->dbkey = (string)$wbs->FRONTEND['dbkey'];
            $dkey_path = $this->source_path.'dblist/'.$this->dbkey.'.xml';

            if (empty($this->dbkey) || !file_exists($dkey_path)) {
                throw new waException(sprintf(_wp('Invalid PATH %s; %s'), $this->source_path, sprintf(_wp('invalid file %s'), 'dblist/'.$this->dbkey.'.xml')));
            }
            /**
             *
             * @var SimpleXMLElement $dblist
             */
            $dblist = simplexml_load_file($dkey_path);

            $host_name = (string)$dblist->DBSETTINGS['SQLSERVER'];
            $host = $wbs->xPath('/WBS/SQLSERVERS/SQLSERVER[@NAME="'.htmlentities($host_name, ENT_QUOTES, 'utf-8').'"]');
            if (!count($host)) {
                throw new waException(_wp('Invalid SQL server name'));
            }
            $host = $host[0];
            $port = (string)$host['PORT'];
            $this->sql_options = array(
                'host'     => (string)$host['HOST'].($port ? ':'.$port : ''),
                'user'     => (string)$dblist->DBSETTINGS['DB_USER'],
                'password' => (string)$dblist->DBSETTINGS['DB_PASSWORD'],
                'database' => (string)$dblist->DBSETTINGS['DB_NAME'],
                'type'     => function_exists('mysqli_connect') ? 'mysqli' : 'mysql',
            );

            $this->source = new waModel($this->sql_options);
        } else {
            $this->source->ping();
        }
        return $this->source;
    }
}