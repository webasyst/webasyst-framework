<?php

class clockWidget extends waWidget
{
    protected $params;
    const TYPE_ELECTRONIC = 'electronic';
    const TYPE_ROUND = 'round';
    const FORMAT_24 = '24';
    const FORMAT_AM = 'am';
    const FORMAT_PM = 'pm';

    public function defaultAction()
    {
        $this->display(array(
            'widget_id' => $this->id,
            'widget_url' => $this->getStaticUrl(),
            'widget_app' => $this->info['app_id'],
            'widget_name' => $this->info['widget'],
            'type' => $this->getType(),
            'format' => $this->getFormat(),
            'offset' => $this->getTimeOffset(),
            'size' => $this->info['size'],
            'source' => $this->getSettings('source'),
            'town' => $this->getSettings('town'),
        ), $this->getTemplatePath(ucfirst($this->getType())).'.html');
    }

    public function getTimeOffset()
    {
        $source = $this->getSettings('source');
        $offset = 0;

        if ($source == "server") {
            $user_timezone = wa()->getUser()->get('timezone');
            $timezone_offset = intval(waDateTime::date('Z', null, $user_timezone, null)); // in second
            $offset = ( $timezone_offset * 1000 );

        } else if ($source == "local") {
            $offset = 0;

        } else {
            $offset = ( $source ) ? $source : 0;
        }

        return $offset;
    }

    public function getType()
    {
        $type = $this->getSettings('type');
        return $type ? $type : self::TYPE_ROUND;
    }

    public function getFormat()
    {
        $format = $this->getSettings('format');
        return $format ? $format : self::FORMAT_24;
    }
}