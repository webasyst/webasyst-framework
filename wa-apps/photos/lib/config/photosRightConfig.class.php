<?php

class photosRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('upload', _w('Can upload photos and create new albums'));
        $this->addItem('edit', _w('Can edit and delete photos and albums uploaded by other users'));
        $this->addItem('pages', _w('Can edit pages'));
    }
}