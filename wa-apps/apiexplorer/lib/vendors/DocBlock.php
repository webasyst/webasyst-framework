<?php

class DocBlock
{

    public $docblock,
        $description = null,
        $all_params = [];

    /**
     * Parses a docblock;
     */
    function __construct($docblock)
    {
        if (!is_string($docblock)) {
            throw new Exception("DocBlock expects first parameter to be a string");
        }

        $this->docblock = $docblock;
        $this->parse_block();
    }

    /**
     * An alias to __call();
     * allows a better DSL
     *
     * @param string $param_name
     *
     * @return mixed
     */
    public function __get($param_name)
    {
        return $this->$param_name();
    }

    /**
     * Checks if the param exists
     *
     * @param string $param_name
     *
     * @return mixed
     */
    public function __call($param_name, $values = null)
    {
        if ($param_name == "description") {
            return $this->description;
        } else {
            if (isset($this->all_params[$param_name])) {
                $params = $this->all_params[$param_name];

                if (count($params) == 1) {
                    return $params[0];
                } else {
                    return $params;
                }
            }
        }

        return null;
    }

    private $desc_stop = false;

    /**
     * Parse each line in the docblock
     * and store the params in `$this->all_params`
     * and the rest in `$this->description`
     */
    private function parse_block()
    {
        $param_name = '';
        // split at each line
        foreach (preg_split("/(\r?\n)/", $this->docblock) as $line) {

            // if starts with an asterisk
            if (preg_match('/^(?=\s+?\*[^\/])(.+)/', $line, $matches)) {

                $info = $matches[1];

                // remove wrapping whitespace
                $info = trim($info);

                // remove leading asterisk
                $info = preg_replace('/^(\*\s+?)/', '', $info);

                // if it doesn't start with an "@" symbol
                // then add to the description
                if ($info[0] !== "@" && !$this->desc_stop) {
                    $this->description .= "\n$info";
                    continue;
                }

                $this->desc_stop = true;
                // get the name of the param
                if (preg_match('/@(\w+)/', $info, $matches)) {
                    $param_name = $matches[1];
                    $info = str_replace("@$param_name ", '', $info);
                }

                // remove the param from the string

                // if the param hasn't been added yet, create a key for it
                if (!isset($this->all_params[$param_name])) {
                    $this->all_params[$param_name] = [];
                }

                // push the param value into place
                $this->all_params[$param_name][] = $info;
            }
        }
    }
}