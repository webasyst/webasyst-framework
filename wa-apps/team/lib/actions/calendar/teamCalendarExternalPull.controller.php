<?php

class teamCalendarExternalPullController extends waLongActionController
{
    /**
     * @var teamCalendarExternalPull
     */
    protected $pull;

    protected function preExecute()
    {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $this->getResponse()->sendHeaders();
    }

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $e) {
            if ($e->getCode() == '302') {
                echo json_encode(array('warning' => $e->getMessage()));
            } else {
                echo json_encode(array('error' => $e->getMessage()));
            }
            $w = date('W');
            $y = date('Y');
            $message = get_class($e) . " - " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
            waLog::log($message, "team/exceptions/{$y}/{$w}.log");
        }
    }

    /**
     * Initializes new process.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     */
    protected function init()
    {
        $this->initData();
    }

    protected function initData()
    {
        $this->data = $this->data + array(
                'timestamp' => time(),
                'progress_info' => array(
                    'done' => false,
                    'ready' => false
                )
            );
    }

    /**
     * Checks if there is any more work for $this->step() to do.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     *
     * $this->getStorage() session is already closed.
     *
     * @return boolean whether all the work is done
     */
    protected function isDone()
    {
        return !empty($this->data['progress_info']['done']);
    }

    /**
     * Performs a small piece of work.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     * Should never take longer than 3-5 seconds (10-15% of max_execution_time).
     * It is safe to make very short steps: they are batched into longer packs between saves.
     *
     * $this->getStorage() session is already closed.
     * @return boolean false to end this Runner and call info(); true to continue.
     */
    protected function step()
    {
        if (!$this->isDone()) {
            $pull = $this->getCalendarExternalPull();
            $pull->execute();
            $process_info = $pull->getProcessInfo();
            $this->data['progress_info']['done'] = !empty($process_info['done']);
        }
    }

    /**
     * @return teamCalendarExternalPull
     */
    public function getCalendarExternalPull()
    {
        if (!$this->pull) {
            $id = (int) wa()->getRequest()->post('id');
            $this->pull = new teamCalendarExternalPull($id);
        }
        return $this->pull;
    }

    /**
     * Called when $this->isDone() is true
     * $this->data is read-only, $this->fd is not available.
     *
     * $this->getStorage() session is already closed.
     *
     * @param $filename string full path to resulting file
     * @return boolean true to delete all process files; false to be able to access process again.
     */
    protected function finish($filename)
    {
        $this->info();
        if ($this->getRequest()->post('cleanup')) {
            return true;
        }
        return false;
    }

    /** Called by a Messenger when the Runner is still alive, or when a Runner
     * exited voluntarily, but isDone() is still false.
     *
     * This function must send $this->processId to browser to allow user to continue.
     *
     * $this->data is read-only. $this->fd is not available.
     */
    protected function info()
    {
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'      => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId' => $this->processId,
            'progress'  => 50,                  // dummy progress
            'done'      => $this->isDone()
        );
        echo json_encode($response);
    }
}
