<?php

class photosRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('upload', _w('Can upload photos and create new albums'));
        $this->addItem('edit', _w('Can edit and delete photos and albums uploaded by other users'));
        $this->addItem('pages', _ws('Can edit pages'));
        $this->addItem('design', _ws('Can edit design'));
    }
}