<?php

if (!class_exists('PEAR')) {
    throw new Exception('Class PEAR required');
}
if (!class_exists('Archive_Tar')) {
    throw new Exception('Class Archive_Tar required');
}

if (!extension_loaded('zlib')) {
    throw new Exception('PHP extension zlib required');
}


class DurableTar extends Archive_Tar implements Serializable
{
    private $tarSize;
    private $lastOffset;
    private $resumeOffset;
    private $resumeMode;
    private $stateHandler;
    private $performanceHandler;
    private $target_path;

    /**
     * @param    string $p_tarname The name of the tar archive to create
     * @param    string $p_compress can be null, 'gz' or 'bz2'. This
     *                   parameter indicates if gzip or bz2 compression
     *                   is required.  For compatibility reason the
     *                   boolean value 'true' means 'gz'.
     * @param int $resumeOffset
     * @param $tarSize
     * @access public
     */
    public function __construct($p_tarname, $p_compress = null, $resumeOffset = 0, $tarSize = 0)
    {
        $this->tarSize = $tarSize;
        $this->resumeOffset = $resumeOffset;
        return parent::__construct($p_tarname, $p_compress);
    }

    public function setResume($resumeOffset = 0, $tarSize = 0)
    {
        $this->tarSize = $tarSize;
        $this->resumeOffset = $resumeOffset;
    }

    public function serialize()
    {
        $data = array(
            'tarSize'      => $this->tarSize,
            'resumeOffset' => $this->resumeOffset,
        );
        return serialize($data);
    }

    public function unserialize($serialized)
    {

    }

    private function resume()
    {

        if ($this->resumeOffset > 1) {
            $this->setState($this->resumeOffset);
            $this->_jumpBlock($this->resumeOffset);
        } else {
            $this->_getSize();
        }
        $this->setState($this->resumeOffset);
        $this->resumeMode = true;
    }

    function _extractList($p_path, &$p_list_detail, $p_mode, $p_file_list, $p_remove_path)
    {
        if ($p_mode == 'complete') {
            $this->target_path = $p_path;
            $this->resume();
        }
        return parent::_extractList($p_path, $p_list_detail, $p_mode, $p_file_list, $p_remove_path);
    }

    function _getOffset($p_len = null)
    {
        $offset = null;
        if (is_resource($this->_file)) {
            if ($p_len === null) {
                $p_len = 512;
            }

            if ($this->_compress_type == 'gz') {
                $offset = @gztell($this->_file) / $p_len;
            } else {
                if ($this->_compress_type == 'bz2') {
                    //Replace missing bztell() and bzseek()
                    $offset = 0;
                } else {
                    if ($this->_compress_type == 'none') {
                        $offset = @ftell($this->_file) / $p_len;
                    } else {
                        $this->_error('Unknown or missing compression type ('.$this->_compress_type.')');
                    }
                }
            }
        }
        return floor($offset);
    }

    function _getSize($p_len = null)
    {
        static $count = 0;
        if ($this->tarSize) {
            return;
        }
        $currentBlock = $this->_getOffset($p_len);
        while (strlen(parent::_readBlock())) {
            $this->_jumpBlock(1024 /*4096*/);
            $tarSize = $this->_getOffset($p_len);
            if ($tarSize > 0) {
                $this->tarSize = $tarSize;
            } else {
                break;
            }
            if (++$count > 128) {
                $count = 0;
                $this->setState($this->resumeOffset);
            }
        }
        $this->_rewind();
        $this->_jumpBlock($currentBlock);
    }

    function _rewind()
    {
        if (is_resource($this->_file)) {
            if ($this->_compress_type == 'gz') {
                @gzrewind($this->_file);
            } else {
                if ($this->_compress_type == 'bz2') {
                    //Replace missing bztell() and bzseek()
                } else {
                    if ($this->_compress_type == 'none') {
                        @rewind($this->_file);
                    } else {
                        $this->_error('Unknown or missing compression type ('.$this->_compress_type.')');
                    }
                }
            }
        }
        return true;
    }

    function _readHeader($v_binary_data, &$v_header)
    {
        static $is_first = true;
        static $count = 0;
        static $block_performance = 32;
        $v_result = parent::_readHeader($v_binary_data, $v_header);

        if ($v_result && $this->resumeMode && ((++$count > $block_performance) || $is_first)) {
            $this->lastOffset = $this->_getOffset() - 1;
            $performance = $this->setState($this->lastOffset);
            if ($is_first) {
                $is_first = false;
            } else {
                $count = 0;
                $block_performance = $performance;
            }
        }
        return $v_result;
    }

    public function setStateHandler($callback)
    {
        if (is_callable($callback) || true) {
            $this->stateHandler =& $callback;
        } else {
            throw new Exception("Invalid callback state handler");
        }
    }

    public function setPerformanceHandler($callback)
    {
        if (is_callable($callback) || true) {
            $this->performanceHandler =& $callback;
        } else {
            throw new Exception("Invalid callback state handler");
        }
    }

    private function setState($offset, $debug = null)
    {
        static $block_performance = 32;
        $state_data = array(
            //'stage_name'=>'extract',
            'resume'              => array(
                'offset'   => $offset,
                'tar_size' => $this->tarSize,
            ),
            'stage_current_value' => $this->lastOffset * 512,
            'stage_value'         => $this->tarSize * 512,
            'debug'               => array('block_performance' => $block_performance, 'additional' => $debug),
        );
        if ($this->stateHandler) {
            $performance = call_user_func($this->stateHandler, $state_data);
            if ($this->performanceHandler) {
                $block_performance = call_user_func($this->performanceHandler, $block_performance, $performance / (8 * 512), 'extract', 8, 16, 128);
            }
        }
        return $block_performance;
    }
}
