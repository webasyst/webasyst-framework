<?php

class photosImportPluginRunController extends waLongActionController
{
    /**
     * @var photosImportTransport
     */
    private $transport;

    protected function init()
    {
        $options = $this->getRequest()->post();
        $class = 'photosImport'.ucfirst($options['transport']).'Transport';
        if (class_exists($class)) {
            unset($options['transport']);
            $this->data['transport'] = new $class($options);
        } else {
            throw new waException('Transport not found');
        }
        $this->transport = &$this->data['transport'];
        $this->transport->init();
        $this->data['current'] = 0;
        $this->data['count'] = $this->transport->count();
        $this->data['type'] = 1; // album
    }

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $ex) {
            echo json_encode(array('error'=>$ex->getMessage()));
        }
    }

    protected function isDone()
    {
        return $this->data['current'] >= $this->data['count'];
    }

    protected function step()
    {
        $this->transport->step($this->data['current']);
    }

    protected function finish($filename)
    {
        $this->info();
        return $this->getRequest()->post('cleanup')? true: false;
    }

    protected function info()
    {
        $response = array(
            'processId' => $this->processId,
            'progress' => (isset($this->data['count']) && $this->data['count']) ? sprintf('%0.2f%%',100.0*$this->data['current']/$this->data['count']) : false,
            'ready' => $this->isDone(),
            'count'=> empty($this->data['count'])?false:$this->data['count'],
        );
        echo json_encode($response);
    }

    protected function restore()
    {
        $this->transport = &$this->data['transport'];
        $this->transport->restore();
    }
}