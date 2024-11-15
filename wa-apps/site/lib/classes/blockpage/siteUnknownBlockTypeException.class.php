<?php
class siteUnknownBlockTypeException extends waException
{
    public $block_type;
    public function __construct(string $type)
    {
        $this->block_type = $type;
        parent::__construct('Unknown block type: '.$type, 500);
    }
}