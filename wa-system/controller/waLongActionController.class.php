<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage controller
 */

/**
 * This controller helps to implement potentially long operations when it's impossible to
 * avoid max execution time limit.
 *
 * Each operation is identified by processId. It is possible to run several processes with
 * differend ids at the same time.
 *
 * Only one instance of (descendant of) this class with given id can be a Runner at given time.
 * Runner is an instance that performs actual work. Runner works until he's done or until
 * he dies exceeding max execution time. Browser must request page again from time to time
 * if old connection gets closed to resume the process.
 *
 * While Runner is alive, all other possible instances will automatically become Messengers.
 * Messenger don't do real work but can access (and give to user) some information about
 * Runner's status.
 *
 * It is possible to keep data that is guaranteed not to became corrupt regardless of
 * when (or if) script fails. $this->data is a persistent array and $this->fd is
 * a file descriptor with this guarantee. Only Runner has write access to this persistent data.
 * All data in $this->data must be serializable.
 *
 * For all methods of this class that are called inside a transaction:
 * 1) If a script fails inside a transaction then all changes to $this->data and
 *    $this->fd file get reverted and are not visible inside subsequent transactions.
 * 2) If transaction completes successfully then all changes to $this->data and $this->fd
 *    are visible to subsequent transactions.
 *
 * Execution time for every function that runs inside a transaction must be reasonably small
 * for this class to be able to keep it's guarantees.
 * Reasonably small = no more than 10% of max execution time for each transaction.
 *
 * Controller's entry point for this class expects a 'processid' get or post parameter.
 * It then becomes available as $this->processId. If id is not given in request,
 * a new process is started and $this->info() is responsible for returning id to user
 * for subsequent operations. $this->newProcess indicates whether this
 * Runner created the process (true) or not (false).
 *
 * $this->finish() gets called when $this->isDone() return true.
 * If $this->finish() return true, then process removes all its files and cannot
 * be accessed again. Otherwise another instance can be called for this process
 * to access the same result data and file.
 *
 * Besides $this->fd and $this->data, $this->max_exec_time is also available for reading.
 * It contains max execution time for this script (false if unknown).
 */
abstract class waLongActionController extends waController
{
    /** Checks if it's ok to initialize a new process.
      * @return boolean true if initialization can start
      */
    protected function preInit() {return true;}

    /** Initializes new process.
      * Runs inside a transaction ($this->data and $this->fd are accessible).
      */
    abstract protected function init();

    /** Checks if there is any more work for $this->step() to do.
      * Runs inside a transaction ($this->data and $this->fd are accessible).
      *
      * $this->getStorage() session is already closed.
      *
      * @return boolean whether all the work is done
      */
    abstract protected function isDone();

    /** Performs a small piece of work.
      * Runs inside a transaction ($this->data and $this->fd are accessible).
      *
      * The longer it takes to complete one step, the more time it is possible to lose if script fails.
      * The shorter, the more overhead there are because of copying $this->data and $this->fd after
      * each step. So, it should be reasonably long and reasonably short at the same time.
      * 5-10% of max execution time is recommended.
      *
      * $this->getStorage() session is already closed.
      * @return boolean false to end this Runner and call info(); true to continue.
      */
    abstract protected function step();

    /** Called when $this->isDone() is true
      * $this->data is read-only, $this->fd is not available.
      *
      * $this->getStorage() session is already closed.
      *
      * @param $filename string full path to resulting file
      * @return boolean true to delete all process files; false to be able to access process again.
      */
    abstract protected function finish($filename);

    /** Called by a new Runner when the old one dies.
      * Should be used to restore any non-persistent data for $this->step() if needed.
      * Runs inside a transaction ($this->data and $this->fd are accessible).
      * $this->getStorage() session is already closed.
      */
    protected function restore() {}

    /** Called by a Messenger when the Runner is still alive, or when a Runner
      * exited voluntarily, but isDone() is still false.
      *
      * This function must send $this->processId to allow user to continue.
      *
      * $this->data is read-only. $this->fd is not available.
      */
    protected function info() {}

    //
    // Implementation details below this point
    //

    // actual source for $this->processId for __get()
    private $_processId = 0;

    // persisnent storage.
    private $_data = array(
        'data' => array(),      // actual source for $this->data for __get() and __set()
        'avg_time' => 15,       // average time in seconds between calls to $this->_save(), total_time/total_saves
        'total_saves' => 0,
        'total_time' => 0,
        'ready' => FALSE,
    );

    // actual source for $this->fd for __get()
    private $_fd = null;

    // Whether we're currently inside a transaction
    private $_transaction = false;

    // Whether it's a Runner object
    private $_runner = false;

    // true if this Runner generated process id himself (didn't get it from request)
    private $_newProcess = false;

    // Files used by this class.
    // Actual filenames are filled in by $this->_initDataStructures() and $this->_obtainLock()
    private $_files = array(
        'new' => array(
            'data' => '',   // file with $this->data serialized.
            'file' => '',   // $this->fd points here. File is locked by a live Runner permanently.
        ),
        'old' => array(     // A second pair of data files to ensure persistance.
            'data' => '',
            'file' => '',
        ),
        'flock_ok' => '',   // this file exists if we're sure that flock works in this system.
    );

    // last call of _save()
    private $_lastSaveTime = 0;

    public function execute()
    {
        // How much time we can safely run?
        $this->_max_exec_time = ini_get('max_execution_time');
        if($this->_max_exec_time <= 0) {
            $this->_max_exec_time = false;
        }

        // We'll try to disable execution time limit.
        // It doesn't always work, but it doesn't hurt either.
        @set_time_limit(0);

        $this->_processId = waRequest::get('processid');
        if (!$this->_processId) {
            $this->_processId = waRequest::post('processid');
        }

        if (!$this->processId) {
            if (!$this->preInit()) {
                return;
            }
            $this->_initDataStructures(); // it calls init() too
            $this->getStorage()->close();
        } else {
            $status = $this->_obtainLock();

            if ($this->_data['ready']) {
                $this->_runner = FALSE;
                if ($this->finish($this->_files['old']['file'])) {
                    $this->_cleanup();
                }
                return;
            }

            switch($status) {
                case 'runner':
                    $this->_transaction = true;
                    $this->restore();
                    $this->_transaction = false;
                    $this->getStorage()->close();
                    break;
                case 'messenger':
                    $this->info();
                    return;
                case 'no process':
                    // must be a lost messenger
                    echo json_encode(array(
                        'ready' => true,
                        'processId' => $this->_processId,
                    ));
                    return;
            }
        }

        $this->_lastSaveTime = explode(' ', microtime());
        $this->_transaction = true;
        $continue = true;
        while($continue && !$this->isDone()) {
            $continue = $this->step();
            $this->_save();
        }

        $this->_transaction = false;
        $this->_runner = FALSE;

        if (!$continue) {
            $this->info();
            return;
        }

        // We're done!

        $this->_data['ready'] = TRUE;
        $this->_save();
        if ($this->finish($this->_files['old']['file'])) {
            $this->_cleanup();
        }
    }

    /** Close $this->_fd and remove all files we created */
    private function _cleanup() {
        @flock($this->_fd, LOCK_UN);
        @fclose($this->_fd);
        unlink($this->_files['new']['data']);
        unlink($this->_files['new']['file']);
        unlink($this->_files['old']['data']);
        @unlink($this->_files['old']['file']);    // could have been moved by finish()
        @unlink($this->_files['flock_ok']);
        rmdir(dirname($this->_files['old']['data']));
    }

    /** Creates private files and $this->... data structures for new process.
      * Initializes $this->_processId, $this->_data, $this->_fd, $this->_runner = true
      * Called once when a process is created. */
    private function _initDataStructures() {
        // Generate new unique id
        $attempts = 3;
        $dir = waSystem::getInstance()->getTempPath('longop').'/';
        do {
            $attempts--;
            $id = uniqid();
        } while ($attempts >= 0 && !@mkdir($dir.$id, 0775));

        if($attempts <= 0) {
            throw new waException('Unable to create unique dir in '.$dir);
        }

        $this->_newProcess = true;
        $this->_processId = $id;
        $this->_runner = TRUE;

        // Create folder, locked files, unlocked files and data files
        $this->_files = $this->_getFilenames();
        touch($this->_files['new']['file']);
        touch($this->_files['old']['file']);

        // init $this->fd
        if (! ( $this->_fd = fopen($this->_files['new']['file'], 'a+b'))) {
            throw new waException('Unable to open file: '.$this->_files['new']['file']);
        }

        // $this->data is already fine, but we have to write data files
        file_put_contents($this->_files['new']['data'], 'garbage');

        // Allowing init() to modify $this->data before we first save it.
        $this->_transaction = TRUE;
        $this->init();
        $this->_transaction = FALSE;
        file_put_contents($this->_files['old']['data'], $this->serializeData($this->_data));
    }

    /** Checks if there's a Runner for $this->processId.
      * If there is one then initializes $this->_data, $this->_runner = false
      * If there are no Runner then initializes $this->_data, $this->_fd, $this->_runner = true
      * Called when old Runner dies to initialize new one.
      * @return string status of this instance: 'runner', 'messenger' or 'no process' if data files not found. */
    private function _obtainLock() {
        $this->_files = $this->_getFilenames();

        if (!file_exists($this->_files['new']['file'])) {
            return 'no process';
        }

        // Main Lock needs stats, so we load data first
        $attempts = 3;
        while ($attempts > 0) {
            if ($attempts < 3) {
                usleep(mt_rand(500, 1500));
            }
            $attempts--;
            if (!file_exists($this->_files['old']['data'])) {
                return 'no process';
            }

            if (! ( $fd = fopen($this->_files['old']['data'], 'rb'))) {
                continue;
            }
            if (!flock($fd, LOCK_SH)) {
                fclose($fd);
                continue;
            }
            $data = $this->unserializeData(file_get_contents($this->_files['old']['data']));
            if (!$data) {
                flock($fd, LOCK_UN); // being paranoid
                fclose($fd);
                continue;
            }
            $this->_data = $data;
            flock($fd, LOCK_UN); // being paranoid
            fclose($fd);
            break;
        }
        if ($attempts <= 0) {
            throw new waException('Messanger is unable to read data from '.$this->_files['old']['data']);
        }

        if (!$this->_mainLock($this->_files['new']['file'], $this->_files['old']['file'])) {
            // A live Runner exists. $this->data is already loaded.
            $this->_runner = FALSE;
            return 'messenger';
        }

        // We're the new Runner.

        $this->_runner = TRUE;
        $this->_loadData();
        return 'runner';
    }

    /** @param $fileContents string data file contents
      * @return Array|boolean Unserialized array or false on failure  */
    private function unserializeData($fileContents) {
        $arr = explode('#', $fileContents, 2);
        if (count($arr) != 2) {
            return false;
        }
        list($length, $serialized) = $arr;
        if (!$serialized || strlen($serialized) != $length) {
            return false;
        }
        $unserialized = unserialize($serialized);
        if (!$unserialized) {
            throw new waException('Unable to unserialize data with correct length.');
        }
        return $unserialized;
    }

    /** @param $array Array data to serialize
      * @return String serialized data */
    private function serializeData($array) {
        $serialized = serialize($array);
        if (!$serialized) {
            throw new waException('Unable to serialize '.print_r($array, TRUE));
        }
        return strlen($serialized).'#'.$serialized;
    }

    /** Loads data from files using filenames from $this->_files
      * Makes sure both $this->_data and $this->_fd contain
      * non-corrupt data, restoring it if needed. */
    private function _loadData() {
        if (!$this->_runner) {
            throw new waException('Cannot _loadData() for Messenger.');
        }

        // Invariant of $this->_save() ensures that when new_data unserializes successfully,
        // then new_file is ok; and when unserialization fails, then old_data and old_file
        // represent consistent state.
        $newData = $this->unserializeData(file_get_contents($this->_files['new']['data']));
        if(!$newData) {
            // use old data
            $this->_data = $this->unserializeData(file_get_contents($this->_files['old']['data']));
            if (!$this->_data) {
                throw new waException('Both sets of data are corrupt in waLongActionController.');
            }

            ftruncate($this->_fd, 0);
            fseek($this->_fd, 0);
            $fd2 = fopen($this->_files['old']['file'], 'rb');
            while( ( $c = fread($fd2, 8192))) {
                fwrite($this->_fd, $c);
            }
            fclose($fd2);
            return;
        }

        // Use new set of data. $this->fd is already good.
        $this->_data = $newData;
    }

    /** Return a new $this->_files structure using $this->processId */
    private function _getFilenames() {
        $dir = waSystem::getInstance()->getTempPath('longop/'.$this->processId);
        return array(
            'new' => array(
                'data' => $dir.'/new_data',
                'file' => $dir.'/new_file',
            ),
            'old' => array(
                'data' => $dir.'/old_data',
                'file' => $dir.'/old_file',
            ),
            'flock_ok' => $dir.'/flock_ok',
        );
    }

    /** Saves current persistent data. */
    private function _save() {

        // invariant:
        // 1) if new_data unserializes successfully, then new_data and new_file contain
        // consistent (non-corrupt) state of the process.
        // 2) if new_data failes to unserialize, then old_data and old_file contain
        // consistent state.

        // At this point 'old' represents good data before current transaction even started;
        // new_data contains garbage; and new_file is in correct state for the end of current transaction.

        fflush($this->_fd);
        $curTime = explode(' ', microtime());
        $this->_data['total_saves']++;
        $this->_data['total_time'] += $curTime[0] + $curTime[1] - $this->_lastSaveTime[0] - $this->_lastSaveTime[1];
        $this->_data['avg_time'] = $this->_data['total_time'] / $this->_data['total_saves'];
        $this->_lastSaveTime = $curTime;
        file_put_contents($this->_files['new']['data'], $this->serializeData($this->_data));

        // Now 'new' represents good data after current transaction.
        // We can now spoil 'old' since we have 'new'.

        copy($this->_files['new']['file'], $this->_files['old']['file']);
        copy($this->_files['new']['data'], $this->_files['old']['data']);

        // Now both 'new' and 'old' represent good data after current transaction.

        // finally, garbage in new_data ensures that invariant stays true
        // while step() works on new_file.
        file_put_contents($this->_files['new']['data'], 'garbage');

        // Reset file position in $this->fd to EOF. Since we don't save file position
        // between different Runner instances, it's more consistent just to reset it every time.
        fseek($this->_fd, -1, SEEK_END);
    }

    /** Our own robust file locking mechanism.
      * Best we can afford still being compatible with everything.
      * Never blocks, except on (damned) Windows in rare circumstances.
      * @return boolean true if lock is obtained, false otherwise */
    private function _mainLock($filename, $filename2) {
        // Flock will always block on Windows systems, regardless of LOCK_NB option.
        // On some combinations of system and HTTP server flock does not
        // work reliably at all. We're sure we can trust flock only if we've already seen it working
        // OR if we've just created unique processId and nobody else knows it yet.

        $waitTime = max($this->_data['avg_time']*2, 3);
        if (!$this->_newProcess && !file_exists($this->_files['flock_ok'])
                && time() < filemtime($filename) + $waitTime) {
            // Recent modification found. Lock failed.
            return false;
        }

        $this->_fd = fopen($filename, 'a+b');

        // Okay. File wasn't modified recently. Trying to obtain flock.
        if (!flock($this->_fd, LOCK_EX|LOCK_NB)) { // On windows it's possible to hang here in flock. Have to live with that.
            // flock failed! Now we're sure it works, so we won't need to check
            // file modification time.
            if (!touch($this->_files['flock_ok'])) {
                throw new waException('Unable to create file: '.$this->_files['flock_ok']);
            }
            flock($this->_fd, LOCK_UN); // being paranoid
            fclose($this->_fd);
            $this->_fd = null;
            return false;
        }

        // We've got the flock. But this doesn't mean anything yet.
        if ($this->_newProcess || file_exists($this->_files['flock_ok'])) {
            // flock is ok in this system, so we're sure there is no other Runner.
            return true;
        }

        // Bad luck, we cannot trust the flock.
        // Fail to lock if the second file was modified reasonably recently.
        // (we must check the second file since the first one is touched by fopen(..., 'ab')
        $waitTime = min($this->_data['avg_time']*3, 20);
        if (time() < filemtime($filename2) + $waitTime) {
            // Recent modification found. Releasing...
            flock($this->_fd, LOCK_UN); // being paranoid
            fclose($this->_fd);
            $this->_fd = null;
            return false;
        }

        // A race condition is possible here between where we check filemtime()
        // and where we touch() the file. However, since we close session only
        // after obtaining this lock (and sessions have their own internal not file-based lock),
        // this pretty much ensures we'll be ok. Still not perfect though.
        touch($filename2);

        // So, finally, we're sure there's no other Runner.
        return true;
    }

    public function &__get($field) {
        switch ($field) {
            case 'data':
                if ($this->_runner && !$this->_transaction) {
                    throw new waException('Data is only accessable inside a transaction.');
                }
                return $this->_data['data']; // by reference
            case 'fd':
                if (!$this->_transaction) {
                    throw new waException('File is only accessable inside a transaction.');
                }
                if (!$this->_runner) {
                    throw new waException('File is only accessable by a Runner.');
                }
                $fd = $this->_fd;
                return $fd; // not by reference
            case 'processId':
                $pid = $this->_processId;
                return $pid; // not by reference
            case 'newProcess':
                $np = $this->_newProcess;
                return $np; // not by reference
            case 'max_exec_time':
                $np = $this->_max_exec_time;
                return $np; // not by reference
            default:
                throw new waException('Unknown property: '.$field);
        }
    }

    public function __set($field, $value) {
        switch ($field) {
            case 'data':
                if (!$this->_transaction) {
                    throw new waException('Data can only be changed inside a transaction.');
                }
                if (!$this->_runner) {
                    throw new waException('Data can only be changed by a Runner.');
                }
                if (!is_array($value)) {
                    throw new waException('Data must be an array.');
                }
                $this->_data['data'] = $value;
                return;
            case 'fd':
                throw new waException('File descriptor is read-only.');
            case 'processId':
                throw new waException('processId is read-only.');
            case 'newProcess':
                throw new waException('newProcess is read-only.');
        }

        // Other not-existing fields are ok.
        $this->$field = $value;
    }
}

// EOF