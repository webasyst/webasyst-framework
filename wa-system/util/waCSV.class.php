<?php

class waCSV
{
    protected $file = false;
    protected $handler;

    public static $delimiters = array(
        0 => array(",", "Comma"),
        1 => array(";", "Semicolon"),
        2 => array("\t", "Tab"),
        //3 => array(".", "Period")
    );
    protected $quote = '"';
    protected $length = 4096; // Max length of the row in the csv file
    protected $extensions = array('txt', 'csv');
    protected $delimiter = ",";
    /**
     * @var array
     */
    protected $fields = array();
    protected $first_line = false;
    public $encode = false;

    public static function getDelimiters()
    {
        $result = array();
        foreach (self::$delimiters as $k => $d) {
            $result[$k] = array($d[0], _ws($d[1]));
        }
        return $result;
    }

    /**
     * Constructor
     *
     * @param $file - path to the source file
     */
    public function __construct($first_line = false, $delimiter = ",", $fields = array(), $file = false)
    {
        $this->first_line = $first_line;
        $this->delimiter = $delimiter;
        $this->fields = $fields;
        if ($file) {
            $this->file = $this->getPath($file);
        }
    }

    public function setEncoding($encoding)
    {
        $this->encode = $encoding;
    }

    public function setFile($file)
    {
        $this->file = $this->getPath($file);
    }

    public function getPath($file)
    {
        return waSystem::getInstance()->getTempPath($file);
    }

    /**
     * Set Fields
     *
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Upload file and returns path to the file
     *
     * @todo Use waRequest::file() and waRequestFile methods.
     * @param $name
     * @return string
     */
    public function upload($name)
    {
        if (!isset($_FILES[$name]) || $_FILES[$name]['error']) {
            throw new waException(_ws("Error uploading file"));
        }

        $file_info = explode(".", $_FILES[$name]['name']);
        if (!in_array(strtolower(end($file_info)), $this->extensions)) {
            throw new waException(_ws("Unknown extension *.").end($file_info));
        }

        $file = uniqid("csv").".csv";
        if (move_uploaded_file($_FILES[$name]['tmp_name'], $this->getPath($file))) {
            $this->file = $this->getPath($file);
        } else {
            throw new waException(_ws('Error moving file'));
        }
        return $file;
    }

    /**
     * Save text in the temp file (for import from text)
     *
     * @param $content
     * @return string
     */
    public function saveContent($content)
    {
        $file = uniqid("csv").".csv";
        if (file_put_contents($this->getPath($file), $content)) {
            $this->file = $this->getPath($file);
        } else {
            throw new waException(_ws('Error moving file'));
        }
        return $file;
    }

    /**
     * Returns stat info about csv-file
     *     array(
     *         'DELIMITER' => ...,
     *         'FIELDS' => array(...), // fields in the first row
     *         'NUM_ROWS' => ... // Count of the rows in the file
     *     )
     *
     * @return array
     */
    public function getInfo()
    {
        if (!$this->file || !file_exists($this->file)) {
            throw new waException(_ws('File does not exist'));
        }
        $h = fopen($this->file, "r");
        if (!$h) {
            throw new waException(_ws("Error open file"));
        }
        // Read the first string
        $string = fgets($h, $this->length);

        // Get delimiter
        if (!$this->delimiter) {
            $max_count_fields = 0;
            foreach (self::$delimiters as $i => $delimiter) {
                $delimiter = $delimiter[0];
                $count_fields = count(explode($delimiter, $string));
                if ($count_fields > $max_count_fields) {
                    $this->delimiter = $delimiter;
                    $max_count_fields = $count_fields;
                }
            }
        }

        // Read fields
        rewind($h);
        $records = array();
        $records[] = $fields = $this->encodeArray(fgetcsv($h, $this->length, $this->delimiter, $this->quote));
        $fields_count = count($fields);

        // Count lines in files
        $n = 0;
        if ($this->first_line) {
            $n = 1;
        }

        while ($n <= 10 && $string = $this->encodeArray(fgetcsv($h, $this->length, $this->delimiter, $this->quote))) {
            if ($this->notEmptyArray($string)) {
                $records[] = $string;
                $count = count($string);
                if ($count > $fields_count) {
                    $fields_count = $count;
                }
                $n++;
            }
        }

        if ($k = $fields_count - count($fields)) {
            for ($i = 0; $i < $k; $i++) {
                $fields[] = "";
            }
        }
        
        if (class_exists('SplFileObject')) {
            fclose($h);
            
            // Hack for fast count strings.
            $file = new SplFileObject($this->file, 'r');
            $file->setFlags(
                SplFileObject::READ_CSV | SplFileObject::READ_AHEAD 
                | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE
            );
            $file->setCsvControl($this->delimiter, $this->quote);
            $file->seek(PHP_INT_MAX);
            $n = $file->key() + 1;
        } else {
            while ($data = fgetcsv($h, $this->length, $this->delimiter, $this->quote)) {
                // Count only not empty strings.
                // A blank line will be returned as an array comprising a single null field. 
                if ($data && $data != array(null)) {
                    $n++;
                }
            }
            fclose($h);
        }

        return array(
            'delimiter' => $this->delimiter,
            'encode' => $this->encode,
            'fields' => $fields,
            'records' => $records,
            'count' => $n
        );
    }

    protected function encodeArray($a)
    {
        if ($this->encode && is_array($a)) {
            foreach ($a as &$v) {
                $v = @ iconv($this->encode, "utf-8//IGNORE", $v);
            }
        }
        return $a;
    }

    protected function notEmptyArray(array $a)
    {
        if (!$a) {
            return false;
        }
        foreach ($a as $v) {
            if (trim($v)) {
                return true;
            }
        }
        return false;
    }

    public function getDelimiterIndex($delimiter)
    {
        foreach (self::$delimiters as $i => $d) {
            if ($d[0] == $delimiter) {
                return $i;
            }
        }
    }

    /**
     * Read CSV-file and returns data
     *
     * @param int $limit
     * @return array
     */
    public function import($limit = 50)
    {
        if (!$this->handler) {
            $this->handler = fopen($this->file, "r");
            if (!$this->handler) {
                throw new waException(_ws("Error open file"));
            }
            if (!$this->first_line) {
                $fields = $this->encodeArray(fgetcsv($this->handler, $this->length, $this->delimiter, $this->quote));
            }
        }
        $data = array();
        $i = 0;
        while (!feof($this->handler) && $limit > $i++) {
            $real_data = $this->encodeArray(fgetcsv($this->handler, $this->length, $this->delimiter, $this->quote));
            if (!$this->notEmptyArray($real_data)) {
                $i--;
                continue;
            }

            if ($this->fields) {
                $info = array();
                foreach ($this->fields as $j => $f) {
                    if ($f) {
                        $info[$f] = $real_data[$j];
                    }
                }
                $data[] = $info;
            } else {
                $data[] = $real_data;
            }
        }
        if (!$data) {
            fclose($this->handler);
            $this->handler = false;
            return false;
        }

        return $data;
    }
}
