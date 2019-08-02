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
 * @subpackage files
 */

require_once dirname(__FILE__).'/gz.php';

/**
 * @todo class under construction (code migrated from Archive_Tar)
 * Class waArchiveTar
 */
class waArchiveTar
{
    protected $path = null;
    protected $file = null;
    protected $files = array();
    protected $mode = 'r';
    protected $compress_type = 'gz';
    protected $ignore_regexp;


    protected $errors = array();

    protected $warnings = array();

    const BLOCK_SIZE = 512;

    /**
     * @see https://www.gnu.org/software/tar/manual/html_node/Standard.html
     */
    const TYPE_REG = 0;        /* regular file */
    const TYPE_AREG = "\0";    /* regular file */
    const TYPE_LNK = 1;        /* link */
    const TYPE_SYM = 2;        /* reserved */
    const TYPE_CHR = 3;        /* character special */
    const TYPE_BLK = 4;        /* block special */
    const TYPE_DIR = 5;        /* directory */
    const TYPE_FIFO = 6;       /* FIFO special */
    const TYPE_CONT = 7;       /* reserved */

    /* Identifies the *next* file on the tape as having a long linkname.  */
    const GNUTYPE_LONGLINK = 'K';

    /* Identifies the *next* file on the tape as having a long name.  */
    const GNUTYPE_LONGNAME = 'L';

    /* Extended header referring to the next file in the archive */
    const XHDTYPE = 'x';

    /* Global extended header */
    const XGLTYPE = 'g';

    protected static $tar_header = array(
        'path' => array(
            'description' => 'File name',
            'size'        => 100,
        ),

        'mode' => array(
            'description' => 'File mode',
            'size'        => 8,
            'format'      => '%07o',
            'value'       => 384,
        ),

        'uid' => array(
            'description' => 'Owner\'s numeric user ID',
            'size'        => 8,
            'format'      => '%07o',
            'optional'    => true,
            'value'       => 0,
        ),

        'gid' => array(
            'description' => 'Group\'s numeric user ID',
            'size'        => 8,
            'format'      => '%07o',
            'optional'    => true,
            'value'       => 0,
        ),

        'size' => array(
            'description' => 'File size in bytes (octal basis)',
            'size'        => 12,
            'format'      => '%011o',
        ),

        'mtime' => array(
            'description' => 'Last modification time in numeric Unix time format (octal)',
            'size'        => 12,
            'format'      => '%011o',
            'pack'        => 'A',//XXX verify it!

        ),

        'chksum' => array(
            'description' => 'Checksum for header record',
            'size'        => 8,
            'format'      => '%07o',
            'data'        => '        ',
        ),

        'typeflag' => array(
            'description' => 'Type flag',
            'size'        => 1,
            'format'      => '%s',
            'value'       => self::TYPE_REG,
        ),

        'linkname' => array(
            'description' => 'Name of linked file',
            'size'        => 100,
            'value'       => '',
        ),

        'magic' => array(
            'description' => 'UStar indicator "ustar"',
            'size'        => 6,
            'value'       => 'ustar ',
            'optional'    => true,
        ),

        'version' => array(
            'description' => 'UStar version "00"',
            'size'        => 2,
            'value'       => ' ',
            'optional'    => true,
        ),

        'uname' => array(
            'description' => 'Owner user name',
            'size'        => 32,
            'value'       => 'Unknown',
            'optional'    => true,
        ),


        'gname' => array(
            'description' => 'Owner group name',
            'size'        => 32,
            'value'       => 'Unknown',
            'optional'    => true,
        ),

        'devmajor' => array(
            'description' => 'Device major number',
            'size'        => 8,
            'value'       => '',
            'optional'    => true,
        ),

        'devminor' => array(
            'description' => 'Device minor number',
            'size'        => 8,
            'value'       => '',
            'optional'    => true,
        ),

        'prefix' => array(
            'description' => 'Filename prefix',
            'size'        => 155,
            'value'       => '',
            'optional'    => true,
        ),

        'alignment' => array(
            'description' => 'byte alignment',
            'size'        => 12,
            'value'       => '',
            'optional'    => true,
        ),
    );

    public function __construct($path, $mode = 'r', $compress_type = null)
    {
        $this->path = $path;
        $this->mode = $mode === 'w' ? 'w' : 'r';
        $this->compress_type = $compress_type;
        $this->init();
    }

    public function __call($name, $arguments)
    {

    }

    public function __destruct()
    {
        $this->close();
    }

    public function __wakeup()
    {
        $this->init();
    }

    public function __sleep()
    {
        // TODO: Implement __sleep() method.
    }

    protected function init()
    {
        $offset = 0;
        foreach (self::$tar_header as &$item) {
            $item['offset'] = $offset;
            $offset += $item['size'];
            unset($item);
        }

        self::$tar_header['mtime']['value'] = time();

        $this->detectCompressType();

        switch ($this->mode) {
            case 'w':
                $this->initWrite();
                break;
            default:
                $this->initRead();
                break;
        }

        if (!$this->file) {
            throw new Exception(sprintf('Path %s could not be opened', $this->path));
        }
    }

    protected function close()
    {
        if (is_resource($this->file)) {
            if (($this->mode == 'w+') || ($this->mode == 'w')) {
                // ----- Write the last 0 filled block for end of archive
                $binary_data = pack('a1024', '');
                $this->writeBlock($binary_data);
            }
            switch ($this->compress_type) {
                case 'gz':
                    @gzclose($this->file);
                    break;
                default:
                    @fclose($this->file);
                    break;
            }
            $this->file = 0;
        }

        if (($this->mode != 'r') && $this->errors) {
            @unlink($this->path);
        }
    }

    protected function detectCompressType()
    {
        if (!in_array($this->compress_type, array('gz', 'none', true, null), true)) {
            $template = 'Unknown or missing compression type (%s)';
            throw new Exception(sprintf($template, $this->compress_type));
        }

        if (($this->compress_type === null) || ($this->compress_type == '')) {
            if (@file_exists($this->path)) {
                if ($fp = @fopen($this->path, "rb")) {
                    // look for gzip magic cookie
                    $data = fread($fp, 2);
                    fclose($fp);
                    if ($data == "\37\213") {
                        $this->compress_type = 'gz';
                        // No sure it's enought for a magic code ....
                    } elseif ($data == "BZ") {
                        $this->compress_type = 'bz2';
                    }
                }
            } else {
                // probably a remote file or some file accessible
                // through a stream interface
                if (substr($this->path, -2) == 'gz') {
                    $this->compress_type = 'gz';
                } elseif ((substr($this->path, -3) == 'bz2') ||
                    (substr($this->path, -2) == 'bz')
                ) {
                    //actually, support ob bz2 removed and not used
                    $this->compress_type = 'bz2';
                }
            }

        } else {
            if (($this->compress_type === true) || ($this->compress_type == 'gz')) {
                $this->$this->compress_type = 'gz';
            }
        }


        if (!in_array($this->compress_type, array('gz', 'none'), true)) {
            $template = 'Unknown or missing compression type (%s)';
            throw new Exception(sprintf($template, $this->compress_type));
        }

        if (($this->compress_type === 'gz') && !extension_loaded('zlib')) {
            throw new Exception("PHP module zlib required");
        }
    }

    protected function initRead()
    {
        if (!@file_exists($this->path)) {
            throw new Exception(sprintf('Path %s does not exists', $this->path));
        }

        switch ($this->compress_type) {
            case 'gz':
                if (function_exists('gzopen')) {
                    $this->file = @gzopen($this->path, "rb");
                } elseif (in_array('compress.zlib', stream_get_wrappers())) {
                    $this->file = @fopen('compress.zlib://'.$this->path, 'rb');
                } else {
                    throw new Exception("Unsupported file extension");
                }

                break;
            default:
                $this->file = @fopen($this->path, "rb");
                break;
        }
    }

    protected function initWrite()
    {
        $size = 0;
        if (@file_exists($this->path) && ($size = filesize($this->path))) {
            if (($this->compress_type != 'none')) {
                throw new Exception(sprintf('Append mode only without compression', $this->path));
            }
        }
        switch ($this->compress_type) {
            case 'gz':
                //round(9 / 100 * $this->level)
                $this->file = @gzopen($this->path, "wb9");
                break;
            default:
                if ($size) {
                    if ($this->file = @fopen($this->path, "r+b")) {

                        // We might have zero, one or two end blocks.
                        // The standard is two, but we should try to handle
                        // other cases.
                        $archive_tar_end_block = pack(sprintf("a%d", self::BLOCK_SIZE), '');
                        @fseek($this->file, $size - 2 * self::BLOCK_SIZE);
                        if (fread($this->file, self::BLOCK_SIZE) == $archive_tar_end_block) {
                            @fseek($this->file, $size - 2 * self::BLOCK_SIZE);
                        } elseif (fread($this->file, self::BLOCK_SIZE) == $archive_tar_end_block) {
                            @fseek($this->file, $size - self::BLOCK_SIZE);
                        }
                        $this->mode = 'w+';
                    }
                } else {
                    $this->file = @fopen($this->path, "wb");
                }
                break;
        }
    }

    protected function writeBlock($binary_data, $len = null)
    {
        if (is_resource($this->file)) {
            if ($len === null) {
                switch ($this->compress_type) {
                    case 'gz':
                        @gzputs($this->file, $binary_data);
                        break;
                    default:
                        @fputs($this->file, $binary_data);
                        break;
                }
            } else {
                switch ($this->compress_type) {
                    case 'gz':
                        @gzputs($this->file, $binary_data, $len);
                        break;
                    default:
                        @fputs($this->file, $binary_data, $len);
                        break;
                }

            }
        }

        return true;
    }

    protected function readBlock()
    {
        $block = null;
        if (is_resource($this->file)) {
            switch ($this->compress_type) {
                case 'gz':
                    $block = @gzread($this->file, self::BLOCK_SIZE);
                    break;
                default:
                    $block = @fread($this->file, self::BLOCK_SIZE);
                    break;
            }
        }

        return $block;
    }

    protected function readBlockStream($size, &$stream = null)
    {
        $result = null;
        $is_stream = is_resource($stream);
        $chunk_size = self::BLOCK_SIZE;
        $n = floor($size / $chunk_size);
        $tail = $size % $chunk_size;
        for ($i = 0; $i <= $n; $i++) {
            if ($tail || ($i < $n)) {
                $data = $this->readBlock();
                if ($i == $n) {
                    $chunk_size = $tail;
                    $data = substr($data, 0, $chunk_size);
                }
                if ($is_stream) {
                    fwrite($stream, $data, $chunk_size);
                } else {
                    $stream .= $data;
                }
            }
        }
    }

    protected function seekBlock($offset = null, $absolute = false)
    {
        if (is_resource($this->file)) {
            if ($offset === null) {
                $offset = 1;
            } else {
                $offset = ceil($offset);
            }
            switch ($this->compress_type) {
                case 'gz':
                    if ($absolute) {
                        $offset = $offset * self::BLOCK_SIZE;
                    } elseif ($offset > 0) {
                        $offset = @gztell($this->file) + ($offset * self::BLOCK_SIZE);
                    } else {
                        $offset = false;
                    }
                    if ($offset !== false) {
                        @gzseek($this->file, $offset);
                    }
                    break;
                default:
                    @fseek($this->file, $offset * self::BLOCK_SIZE, $absolute ? SEEK_SET : SEEK_CUR);
                    break;

            }
        }

        return true;
    }


    protected function getOffset()
    {
        $offset = null;
        if (is_resource($this->file)) {
            switch ($this->compress_type) {
                case 'gz':
                    $offset = @gztell($this->file) / self::BLOCK_SIZE;
                    break;
                default:
                    $offset = @ftell($this->file) / self::BLOCK_SIZE;
                    break;
            }
        }

        return (int)floor($offset);
    }

    protected function rewind()
    {
        if (is_resource($this->file)) {
            switch ($this->compress_type) {
                case 'gz':
                    @gzrewind($this->file);
                    break;
                default:
                    @rewind($this->file);
                    break;
            }
        }

        return true;
    }


    /**
     * This method sets the regular expression for ignoring files and directories
     * at import, for example:
     * $arch->setIgnoreRegexp("#CVS|\.svn#");
     * @param string $regexp regular expression defining which files or directories to ignore
     */
    public function setIgnoreRegexp($regexp)
    {
        $this->ignore_regexp = $regexp;
    }

    /**
     * This method sets the regular expression for ignoring all files and directories
     * matching the filenames in the array list at import, for example:
     * $arch->setIgnoreList(array('CVS', '.svn', 'bin/tool'));
     * @param array $list a list of file or directory names to ignore
     */
    public function setIgnoreList($list)
    {
        $list = (array)$list;
        foreach ($list as &$item) {
            $item = preg_quote($item, '#');
            unset($item);
        }
        $regexp = '#/'.join('$|/', $list).'#';
        $this->setIgnoreRegexp($regexp);
    }

    public function listContent()
    {
        return $this->extractInternal('', "list", '', '');
    }

    public function extract($path = '')
    {
        return $this->extractInternal($path, "complete", 0, '');
    }

    public function extractModify($path, $remove_path)
    {
        return $this->extractInternal($path, "complete", 0, $remove_path);
    }

    /**
     * This method extract from the archive only the files indicated in the
     * $files_list. These files are extracted in the current directory or
     * in the directory indicated by the optional $path parameter.
     * If indicated the $remove_path can be used in the same way as it is
     * used in extractModify() method.
     * @param array $files_list An array of filenames and directory names,
     *                               or a single string with names separated
     *                               by a single blank space.
     * @param string $path The path of the directory where the
     *                               files/dir need to by extracted.
     * @param string $remove_path Part of the memorized path that can be
     *                               removed if present at the beginning of
     *                               the file/dir path.
     * @return array[string]                      true on success, false on error.
     * @see extractModify()
     */
    public function extractList($files_list, $path = '', $remove_path = '')
    {
        return $this->extractInternal($path, "partial", $this->workupList($files_list), $remove_path);
    }

    /**
     * This method extract from the archive one file identified by $filename.
     * The return value is a string with the file content, or NULL on error.
     * @todo merge this method with self::extractInternal
     * @param string $filename The path of the file to extract in a string.
     * @return string a string with the file content or NULL.
     * @throws Exception
     */
    public function extractInString($filename)
    {
        $file = null;
        if (isset($this->files[$filename])) {
            $file = $this->files[$filename];
            $this->seekBlock($file['offset'], true);
        } elseif ($this->files) {
            $file = end($this->files);
            $this->seekBlock($file['offset'], true);
            unset($file);
        }

        while (strlen($binary_data = $this->readBlock())) {

            $header = $this->workupHeader($binary_data);

            if ($header === false) {
                continue;
            }

            if ($header['path'] == $filename) {
                if ($header['typeflag'] === self::TYPE_DIR) {
                    $message = 'Unable to extract in string a directory '.'entry {%s}';
                    throw new Exception($this->error($message, $header['path']));
                } else {
                    $result_str = '';
                    $this->readBlockStream($header['size'], $result_str);

                    return $result_str;
                }
            } elseif (!empty($file)) {
                $message = 'Invalid path for file "%s" : %s expected';
                throw new Exception($this->error($message, $filename, $header['path']));
            } else {
                $this->seekBlock(ceil(($header['size'] / self::BLOCK_SIZE)));
            }
        }

        return null;
    }

    /**
     * This method add the files / directories that are listed in $files_list in
     * the archive. If the archive does not exist it is created.
     * The method return false and a PEAR error text.
     * The files and directories listed are only added at the end of the archive,
     * even if a file with the same name is already archived.
     * See also createModify() method for more details.
     *
     * @param array $files_list An array of filenames and directory names, or a
     *                           single string with names separated by a single
     *                           blank space.
     * @return                   true on success, false on error.
     * @see createModify()
     */
    public function add($files_list)
    {
        return $this->addModify($files_list, '', '');
    }

    /**
     * This method add the files / directories listed in $file_list at the
     * end of the existing archive. If the archive does not yet exists it
     * is created.
     * The $file_list parameter can be an array of string, each string
     * representing a filename or a directory name with their path if
     * needed. It can also be a single string with names separated by a
     * single blank.
     * The path indicated in $remove_dir will be removed from the
     * memorized path of each file / directory listed when this path
     * exists. By default nothing is removed (empty path '')
     * The path indicated in $add_dir will be added at the beginning of
     * the memorized path of each file / directory listed. However it can
     * be set to empty ''. The adding of a path is done after the removing
     * of path.
     * The path add/remove ability enables the user to prepare an archive
     * for extraction in a different path than the origin files are.
     * If a file/dir is already in the archive it will only be added at the
     * end of the archive. There is no update of the existing archived
     * file/dir. However while extracting the archive, the last file will
     * replace the first one. This results in a none optimization of the
     * archive size.
     * If a file/dir does not exist the file/dir is ignored. However an
     * error text is send to PEAR error.
     * If a file/dir is not readable the file/dir is ignored. However an
     * error text is send to PEAR error.
     *
     * @param array $file_list An array of filenames and directory
     *                                   names, or a single string with names
     *                                   separated by a single blank space.
     * @param string $add_dir A string which contains a path to be
     *                                   added to the memorized path of each
     *                                   element in the list.
     * @param string $remove_dir A string which contains a path to be
     *                                   removed from the memorized path of
     *                                   each element in the list, when
     *                                   relevant.
     * @return                           true on success, false on error.
     */
    public function addModify($file_list, $add_dir, $remove_dir = '')
    {
        return $this->createModify($file_list, $add_dir, $remove_dir);
    }


    /**
     * This method creates the archive file and add the files / directories
     * that are listed in $file_list.
     * If a file with the same name exist and is writable, it is replaced
     * by the new tar.
     * The method return false and a PEAR error text.
     * The $file_list parameter can be an array of string, each string
     * representing a filename or a directory name with their path if
     * needed. It can also be a single string with names separated by a
     * single blank.
     * For each directory added in the archive, the files and
     * sub-directories are also added.
     * See also createModify() method for more details.
     *
     * @param array|string $file_list An array of filenames and directory names, or a
     *                           single string with names separated by a single
     *                           blank space.
     * @return                   true on success, false on error.
     * @see createModify()
     */
    public function create($file_list)
    {
        return $this->createModify($file_list, '', '');
    }

    /**
     * This method creates the archive file and add the files / directories
     * that are listed in $file_list.
     * If the file already exists and is writable, it is replaced by the
     * new tar. It is a create and not an add. If the file exists and is
     * read-only or is a directory it is not replaced. The method return
     * false and a PEAR error text.
     * The $file_list parameter can be an array of string, each string
     * representing a filename or a directory name with their path if
     * needed. It can also be a single string with names separated by a
     * single blank.
     * The path indicated in $remove_dir will be removed from the
     * memorized path of each file / directory listed when this path
     * exists. By default nothing is removed (empty path '')
     * The path indicated in $add_dir will be added at the beginning of
     * the memorized path of each file / directory listed. However it can
     * be set to empty ''. The adding of a path is done after the removing
     * of path.
     * The path add/remove ability enables the user to prepare an archive
     * for extraction in a different path than the origin files are.
     * See also addModify() method for file adding properties.
     *
     * @param array|string $file_list An array of filenames and directory names,
     *                               or a single string with names separated by
     *                               a single blank space.
     * @param string $add_dir A string which contains a path to be added
     *                               to the memorized path of each element in
     *                               the list.
     * @param string $remove_dir A string which contains a path to be
     *                               removed from the memorized path of each
     *                               element in the list, when relevant.
     * @return boolean               true on success, false on error.
     * @see addModify()
     */
    public function createModify($file_list, $add_dir, $remove_dir = '')
    {
        $result = true;

        if ($file_list != '') {
            $result = $this->addList($this->workupList($file_list), $add_dir, $remove_dir);
        }

        return $result;
    }

    protected function workupList($file_list)
    {
        if (is_array($file_list)) {
            $list = $file_list;
        } elseif (is_string($file_list)) {
            $list = explode(' ', $file_list);
        } else {
            throw new Exception($this->error('Invalid file list'));
        }

        return $list;
    }

    protected function addList($list, $add_dir, $remove_dir)
    {
        $result = true;

        // ----- Remove potential windows directory separator
        $add_dir = $this->translateWinPath($add_dir);
        $remove_dir = $this->translateWinPath($remove_dir, false);


        if (count($list) == 0) {
            return true;
        }

        foreach ($list as $filename) {
            if (!$result) {
                break;
            }
            $as_filename = null;

            //XXX it's internal hack
            if (is_array($filename)) {
                @list($as_filename, $filename) = $filename;
            }

            // ----- Skip the current tar name
            if ($filename == $this->path) {
                continue;
            }

            if ($filename == '') {
                continue;
            }

            // ----- ignore files and directories matching the ignore regular expression
            if ($this->ignore_regexp && preg_match($this->ignore_regexp, '/'.$filename)) {
                $this->warning("File '$filename' ignored");
                continue;
            }

            if (!file_exists($filename)) {
                $this->warning("File '$filename' does not exist");
                continue;
            }

            // ----- Add the file or directory header
            if (!$this->addFile($filename, $add_dir, $remove_dir, $as_filename)) {
                return false;
            }

            if (@is_dir($filename) && !@is_link($filename)) {
                if (!($dir_handler = opendir($filename))) {
                    $this->warning("Directory '$filename' can not be read");
                    continue;
                }
                while (false !== ($item_handler = readdir($dir_handler))) {
                    if (($item_handler != '.') && ($item_handler != '..')) {
                        if ($filename != ".") {
                            $temp_list[0] = $filename.'/'.$item_handler;
                        } else {
                            $temp_list[0] = $item_handler;
                        }

                        $result = $this->addList($temp_list, $add_dir, $remove_dir);
                    }
                }

                unset($temp_list);
                unset($dir_handler);
                unset($item_handler);
            }
        }

        return $result;
    }

    protected function addFile($filename, $add_dir, $remove_dir, $as_filename = null)
    {
        $this->verifyItemName($filename);

        // ----- Calculate the stored filename
        $filename = $this->translateWinPath($filename, false);
        $stored_filename = ($as_filename) ? $this->translateWinPath($as_filename, false) : $filename;
        if (strcmp($filename, $remove_dir) == 0) {
            return true;
        }
        if ($remove_dir != '') {
            if (substr($remove_dir, -1) != '/') {
                $remove_dir .= '/';
            }

            if (substr($filename, 0, strlen($remove_dir)) == $remove_dir) {
                $stored_filename = substr($filename, strlen($remove_dir));
            }
        }
        $stored_filename = $this->translateWinPath($stored_filename);
        if ($add_dir != '') {
            if (substr($add_dir, -1) == '/') {
                $stored_filename = $add_dir.$stored_filename;
            } else {
                $stored_filename = $add_dir.'/'.$stored_filename;
            }
        }

        $stored_filename = $this->pathReduction($stored_filename);

        if ($this->isArchive($filename)) {
            if (($file = @fopen($filename, "rb")) == 0) {
                $this->warning("Unable to open file '".$filename."' in binary read mode");

                //XXX
                return true;
            }

            $this->writeHeader($filename, $stored_filename);

            while (($buffer = fread($file, self::BLOCK_SIZE)) != '') {
                $this->writeDataBlock($buffer);
            }

            fclose($file);

        } else {
            // ----- Only header for dir
            $this->writeHeader($filename, $stored_filename);
        }

        return true;
    }

    /**
     * This method add a single string as a file at the
     * end of the existing archive. If the archive does not yet exists it
     * is created.
     *
     * @param string $filename A string which contains the full
     *                                   filename path that will be associated
     *                                   with the string.
     * @param string $string The content of the file added in
     *                                   the archive.
     * @return                           true on success, false on error.
     */

    public function addString($filename, $string)
    {
        $this->verifyItemName($filename);

        $data = array(
            'path' => $this->translateWinPath($filename, false),
            'size' => strlen($string),
        );

        $this->writeHeaderBlock($data);

        $i = 0;
        while (($buffer = substr($string, (($i++) * self::BLOCK_SIZE), self::BLOCK_SIZE)) != '') {
            $this->writeDataBlock($buffer);
        }

        return true;
    }


    /**
     * @param $path
     * @param $mode
     * @param $file_list
     * @param $remove_path
     * @return array
     * @throws Exception
     */
    private function extractInternal($path, $mode, $file_list, $remove_path)
    {
        $counter = 0;

        $path = $this->workupExtractPath($path);

        $remove_path = $this->translateWinPath($remove_path);

        // ----- Look for path to remove format (should end by /)
        if (($remove_path != '') && (substr($remove_path, -1) != '/')) {
            $remove_path .= '/';
        }

        switch ($mode) {
            case "complete":
                $extract_all = true;
                $listing = false;
                break;
            case "partial":
                $extract_all = false;
                $listing = false;
                break;
            case "list":
                $extract_all = false;
                $listing = true;
                break;
            default:
                $message = 'Invalid extract mode (%s)';
                throw new Exception($this->error($message, $mode));
        }

        clearstatcache();

        $offset_map = array();

        if ($_next_offset = $this->getOffset()) {
            if ((!$extract_all) && (is_array($file_list))) {
                //optimize partial extract
                $map = array();
                foreach ($file_list as $_path) {
                    if (substr($_path, -1) == '/') {
                        foreach ($this->files as $_file) {
                            if (strpos($_file['path'], $_path) === 0) {
                                $map[$_file['offset']] = $_file['path'];
                            }
                        }
                    } elseif (isset($this->files[$_path])) {
                        $map[$this->files[$_path]['offset']] = $_path;
                    } else {
                        $map = array();
                        break;
                    }
                }
                if ($map) {
                    ksort($map, SORT_NUMERIC);

                    reset($map);
                    $this->seekBlock(key($map), true);
                    $map = array_reverse($map, true);

                    $_next_offset = false; // file read up to the end
                    foreach ($map as $_offset => $_path) {
                        $offset_map[$_path] = $_next_offset;
                        $_next_offset = $_offset;
                    }

                } else {
                    $this->rewind();
                }

            } else {
                $this->rewind();
            }
        }

        while (strlen($binary_data = $this->readBlock()) != 0) {

            $header = $this->workupHeader($binary_data);
            if ($header === false) {
                continue;
            }

            $this->workupItemPath($path, $remove_path, $header);

            if ((!$extract_all) && (is_array($file_list))) {
                // ----- By default no unzip if the file is not found
                $extract_file = $this->pathMatched($header['filename'], $file_list);
            } else {
                $extract_file = true;
            }

            // ----- Look if this file need to be extracted
            if (($extract_file) && (!$listing)) {
                if ($extract_file) {
                    if (file_exists($header['path'])) {
                        $this->verifyItem($header);
                    } else {
                        // ----- Check the directory availability and create it if necessary
                        $directory_path = ($header['typeflag'] === self::TYPE_DIR) ? $header['path'] : dirname($header['path']);
                        try {
                            $this->writeDirectory($directory_path);
                        } catch (Exception  $ex) {
                            $message = 'Unable to create path for [%s] because: %s';
                            throw new Exception($this->error($message, $header['path'], $ex->getMessage()));
                        }
                    }

                    $this->writeItem($header);
                    if ($file_list && is_array($file_list)) {
                        if (isset($offset_map[$header['filename']])) {
                            $_next_offset = $offset_map[$header['filename']];
                            if ($_next_offset !== false) {
                                $_offset = ceil(($header['size'] / self::BLOCK_SIZE)) + $header['offset'] + 1;
                                if ($_offset < $_next_offset) {
                                    $this->seekBlock($_next_offset, true);
                                }
                            } else {
                                break;
                            }
                        }
                    }
                } else {
                    $this->seekBlock(ceil(($header['size'] / self::BLOCK_SIZE)));
                }
            } else {
                $this->seekBlock(ceil(($header['size'] / self::BLOCK_SIZE)));
            }


        }

        return $this->files;
    }

    private function workupHeader($binary_data)
    {

        //check on end blocks it;
        $header = array();
        $header['offset'] = $this->getOffset() - 1;
        $this->readHeader($binary_data, $header);

        if ($header['path'] === '') {
            return false;
        }

        //XXX check other typeflag?
        if (($header['typeflag'] === self::XHDTYPE) || ($header['typeflag'] === self::XGLTYPE)) {
            // ignore extended / pax headers
            $this->seekBlock(ceil(($header['size'] / self::BLOCK_SIZE)));

            return false;
        }

        if ($header['typeflag'] === self::GNUTYPE_LONGNAME) {
            // ----- Look for long filename
            $this->readLongHeader($header);
        }

        //TODO check collision
        $header['filename'] = $header['path'];
        $this->files[$header['path']] = $header;

        return $header;
    }

    protected function pathMatched($path, $file_list)
    {
        $matched = false;

        foreach ($file_list as $pattern) {
            // ----- Look if it is a directory
            if (substr($pattern, -1) == '/') {
                // ----- Look if the directory is in the filename path
                if (strpos($path, $pattern) === 0) {
                    $matched = true;
                    break;
                }
            } elseif ($pattern == $path) {
                // ----- It is a file, so compare the file names
                $matched = true;
                break;
            }
        }

        return $matched;
    }

    private function workupExtractPath($path)
    {
        $path = $this->translateWinPath($path, false);
        if ($path == '' || (
                (substr($path, 0, 1) != '/')
                && (substr($path, 0, 3) != "../")
                && !strpos($path, ':')
            )
        ) {
            $path = "./".$path;
        }

        if (($path != './') && ($path != '/')) {
            $path = rtrim($path);
        }

        return $path;
    }

    private function workupItemPath($path, $remove_path, &$header)
    {
        $remove_path_size = strlen($remove_path);

        // ----- XXX
        if (preg_match('@^.+/([^/]+/)$@', $remove_path, $remove_match)) {
            $remove_path_ = $remove_match[1];
            $remove_path_size_ = strlen($remove_path_);
        } else {
            $remove_path_ = '';
            $remove_path_size_ = 0;
        }


        if (($remove_path != '')
            && (substr($header['path'], 0, $remove_path_size) == $remove_path)
        ) {
            $header['path'] = substr($header['path'], $remove_path_size);
        } elseif (($remove_path_ != '')
            && (substr($header['path'], 0, $remove_path_size_) == $remove_path_)
        ) {
            $header['path'] = substr($header['path'], $remove_path_size_);
        }

        if (($path != './') && ($path != '/')) {
            $header['path'] = ltrim($header['path'], '/');
            $header['path'] = $path.'/'.$header['path'];
        }
    }

    protected function verifyItemName($name)
    {
        if ($name == '') {
            throw new Exception($this->error('Invalid file name'));
        }
    }


    private function verifyItem($header)
    {
        if ((@is_dir($header['path'])) && ($header['typeflag'] === '')) {
            $message = $this->error('File {%s} already exists as a directory', $header['path']);
            throw new Exception($message);
        }

        if (($this->isArchive($header['path']))
            && ($header['typeflag'] === self::TYPE_DIR)) {
            $message = $this->error('Directory {%s} already exists as a file', $header['path']);
            throw new Exception($message);
        }

        if (!is_writeable($header['path'])) {
            $message = $this->error('File {%s} already exists and is write protected', $header['path']);
            throw new Exception($message);
        }

        if (@filemtime($header['path']) > $header['mtime']) {
            // To be completed : An error or silent no replace ?
        }
    }


    /**
     * Check if a directory exists and create it (including parent
     * dirs) if not.
     *
     * @param string $directory_path directory to check
     * @throws Exception
     */
    private function writeDirectory($directory_path)
    {
        clearstatcache();
        if (!@is_dir($directory_path) && ($directory_path != '')) {
            $parent_directory_path = dirname($directory_path);

            if (($parent_directory_path != $directory_path) &&
                ($parent_directory_path != '')) {
                $this->writeDirectory($parent_directory_path);
            }

            if (!@mkdir($directory_path, 0777)) {
                $message = $this->error("Unable to create directory {%s}", $directory_path);
                throw new Exception($message);
            }
        }
    }

    private function writeItem($header)
    {
        switch ($header['typeflag']) {
            case self::TYPE_DIR:
                if (!@file_exists($header['path'])) {
                    if (!@mkdir($header['path'], 0777)) {
                        $message = $this->error('Unable to create directory {%s}', $header['path']);
                        throw new Exception($message);
                    }
                }
                break;

            case self::TYPE_SYM:
                if (@file_exists($header['path'])) {
                    @unlink($header['path']);
                }
                if (!@symlink($header['linkname'], $header['path'])) {
                    $message = $this->error('Unable to extract symbolic link {%s}', $header['path']);
                    throw new Exception($message);
                }
                break;

            case self::TYPE_REG:
            default:
                if (($destiny_file = @fopen($header['path'], "wb")) == 0) {
                    $message = $this->error('Error while opening {%s} in write binary mode', $header['path']);
                    throw new Exception($message);
                } else {
                    try {
                        $this->readBlockStream($header['size'], $destiny_file);
                    } catch (Exception $ex) {
                        @fclose($destiny_file);
                        throw $ex;
                    }

                    @fclose($destiny_file);
                    $this->setItemProperties($header);
                }

                // ----- Check the file size
                clearstatcache();
                if (filesize($header['path']) != $header['size']) {
                    $message = 'Extracted file %s does not have the correct file size \'%d\' (%d expected). Archive may be corrupted.';
                    $message = $this->error($message, $header['path'], filesize($header['path']), $header['size']);
                    throw new Exception($message);
                }
                break;
        }
    }

    // ----- Change the file mode, mtime
    private function setItemProperties($header)
    {
        @touch($header['path'], $header['mtime']);
        if ($header['mode'] & 0111) {
            // make file executable, obey umask
            $mode = fileperms($header['path']) | (~umask() & 0111);
            @chmod($header['path'], $mode);
        }
    }

    private function packField($data, $field, &$chksum = null)
    {
        $info = self::$tar_header[$field];
        $value = isset($data[$field]) ? $data[$field] : (isset($info['value']) ? $info['value'] : null);
        if ($value === null) {
            $value = $info['data'];
        } else {
            $value_format = isset($info['format']) ? $info['format'] : '%s';
            $value = sprintf($value_format, $value);
        }

        $pack_format = isset($info['pack']) ? $info['pack'].$info['size'] : 'a'.$info['size'];
        $block = pack($pack_format, $value);
        if ($chksum !== null) {
            $array = str_split($block);
            $chksum += array_sum(array_map('ord', $array));
        }

        return $block;
    }

    private function pack($data)
    {
        $chksum = 0;
        $block = array();
        if (isset($data['typeflag']) && ($data['typeflag'] == self::TYPE_DIR)) {
            $data['size'] = 0;
        }

        foreach (self::$tar_header as $field => $info) {
            $block[$field] = $this->packField($data, $field, $chksum);
        }

        $data['chksum'] = $chksum;
        $block['chksum'] = $this->packField($data, 'chksum');

        return implode($block);
    }

    private function unpackSizeField($value)
    {
        /**
         * First byte of size has a special meaning if bit 7 is set.
         *
         * Bit 7 indicates base-256 encoding if set.
         * Bit 6 is the sign bit.
         * Bits 5:0 are most significant value bits.
         */
        $ch = ord($value[0]);
        if ($ch & 0x80) {
            // Full 12-bytes record is required.
            $rec_str = $value."\x00";

            $value = ($ch & 0x40) ? -1 : 0;
            $value = ($value << 6) | ($ch & 0x3f);

            for ($num_ch = 1; $num_ch < 12; ++$num_ch) {
                $value = ($value * 256) + ord($rec_str[$num_ch]);
            }

            return $value;
        }

        return false;
    }

    private function unpackField($data, $field)
    {
        $value = isset($data[$field]) ? $data[$field] : null;

        if ($field == 'size') {
            $size = $this->unpackSizeField($value);
            if ($size !== false) {
                return $size;
            }
        }

        $value = rtrim(trim($value, " "), "\0 ");

        if (isset(self::$tar_header[$field]['format'])) {
            if ($values = sscanf($value, self::$tar_header[$field]['format'])) {
                $value = reset($values);
            }
        }

        if (($field == 'typeflag') && is_int($value)) {
            $value = intval($value);
        }

        return $value;
    }

    private function unpack($block, $extend = false)
    {
        $raw = unpack($this->getUnpackFormat(), $block);

        $data = array(
            'chksum' => $this->unpackField($raw, 'chksum'),
        );

        $checksum = $this->getChecksum($block);

        if ($data['chksum'] != $checksum) {
            $data['path'] = '';

            // ----- Look for last block (empty block)
            if (($checksum == 256) && ($data['chksum'] == 0)) {
                return $data;
            }

            $error = 'Invalid checksum for file "%s" : %s calculated, %s expected';
            $data['path'] = $this->unpackField($raw, 'path');
            $message = $this->error($error, $data['path'], $checksum, $data['chksum']);
            throw new Exception($message);
        }

        foreach (self::$tar_header as $field => $info) {
            if (($field !== 'chksum') && ($extend || empty($info['optional']))) {
                $data[$field] = $this->unpackField($raw, $field);
            }
        }

        if ($this->maliciousFilename($data['path'])) {
            $message = 'Malicious .tar detected, file "%s" will not install in desired directory tree';
            $message = $this->error($message, $data['path']);
            throw new Exception($message);
        }

        if ($data['typeflag'] == self::TYPE_DIR) {
            $data['size'] = 0;
        }

        return $data;
    }

    private function getChecksum($block)
    {
        $chksum = 0;
        foreach (self::$tar_header as $field => $info) {

            if ($field !== 'chksum') {
                $block_chunk = substr($block, $info['offset'], $info['size']);
            } else {
                $block_chunk = $info['data'];
            }

            $chksum += array_sum(array_map('ord', str_split($block_chunk)));
        }

        return $chksum;
    }

    private function getUnpackFormat()
    {
        static $format = null;
        if (empty($format)) {
            if (version_compare(PHP_VERSION, "5.5.0-dev") < 0) {
                $template = 'a%d%s';
            } else {
                $template = 'Z%d%s';
            }
            $format_chunks = array();
            foreach (self::$tar_header as $field => &$item) {
                $format_chunks[] = $item['unpack'] = sprintf($template, $item['size'], $field);
                unset($item);
            }
            $format = implode('/', $format_chunks);
        }

        return $format;
    }

    protected function writeHeader($filename, $stored_filename)
    {
        if ($stored_filename == '') {
            $stored_filename = $filename;
        }

        $info = lstat($filename);
        $uid = $info[4];
        $gid = $info[5];
        $perms = $info['mode'] & 000777;

        $mtime = $info['mtime'];

        $linkname = '';

        if (@is_link($filename)) {
            $typeflag = self::TYPE_SYM;
            $linkname = readlink($filename);
            $size = 0;
        } elseif (@is_dir($filename)) {
            $typeflag = self::TYPE_DIR;
            $size = 0;
        } else {
            $typeflag = self::TYPE_REG;
            clearstatcache();
            $size = $info['size'];
        }

        if (function_exists('posix_getpwuid')) {
            $userinfo = posix_getpwuid($info[4]);
            $groupinfo = posix_getgrgid($info[5]);

            $uname = $userinfo['name'];
            $gname = $groupinfo['name'];
        } else {
            $uname = '';
            $gname = '';
        }

        $data = array(
            'path'     => $stored_filename,
            'mode'     => $perms,
            'uid'      => $uid,
            'gid'      => $gid,
            'size'     => $size,
            'mtime'    => $mtime,
            'linkname' => $linkname,
            'typeflag' => $typeflag,
            'gname'    => $gname,
            'uname'    => $uname,
        );

        $this->writeHeaderBlock($data);
    }

    protected function writeHeaderBlock($data)
    {
        $data['path'] = $this->pathReduction($data['path']);

        if (strlen($data['path']) > 99) {
            $this->writeLongHeader($data['path']);
        }

        $binary_data = $this->pack($data);

        $this->writeBlock($binary_data, self::BLOCK_SIZE);
    }

    protected function writeDataBlock($data)
    {
        $binary_data = pack(sprintf("a%d", self::BLOCK_SIZE), (string)$data);
        $this->writeBlock($binary_data);
    }

    protected function writeLongHeader($filename)
    {
        $binary_data = $this->pack(
            array(
                'path'     => '././@LongLink',
                'mode'     => 0,
                'typeflag' => self::GNUTYPE_LONGNAME,
                'size'     => strlen($filename),
                'mtime'    => 0,
            )
        );

        $this->writeBlock($binary_data);

        // ----- Write the filename as content of the block
        $i = 0;
        $block = self::BLOCK_SIZE;
        while (($buffer = substr($filename, (($i++) * $block), $block)) != '') {
            $this->writeDataBlock($buffer);
        }

        return true;
    }

    protected function readLongHeader(&$header)
    {
        $filename = '';
        $n = floor($header['size'] / self::BLOCK_SIZE);
        for ($i = 0; $i < $n; $i++) {
            $content = $this->readBlock();
            $filename .= $content;
        }
        if (($header['size'] % self::BLOCK_SIZE) != 0) {
            $content = $this->readBlock();
            $filename .= trim($content);
        }

        $filename = rtrim(trim($filename), "\0");

        if ($this->maliciousFilename($filename)) {
            $message = 'Malicious .tar detected, file "%s" will not install in desired directory tree';
            //XXX TODO break or ignore invalid data at archive?
            $message = $this->error($message, $filename);
            throw new Exception($message);

        }

        // ----- Read the next header
        $binary_data = $this->readBlock();

        $this->readHeader($binary_data, $header);

        $header['path'] = $filename;
    }

    protected function readHeader($binary_data, &$header)
    {
        if (!is_array($header)) {
            $header = array();
        }

        if (strlen($binary_data) == 0) {
            $header['path'] = '';

            return;
        }

        if (strlen($binary_data) != self::BLOCK_SIZE) {
            $message = $this->error('Invalid block size : %d', strlen($binary_data));
            throw new Exception($message);
        }

        $header = $this->unpack($binary_data) + $header;
    }

    /**
     * Compress path by changing for example "/dir/foo/../bar" to "/dir/bar",
     * and remove double slashes.
     *
     * @param string $dir path to reduce
     *
     * @return string reduced path
     *
     * @access private
     *
     */
    protected function pathReduction($dir)
    {
        $path = '';

        // ----- Look for not empty path
        if ($dir != '') {
            // ----- Explode path by directory names
            $list = explode('/', $dir);

            // ----- Study directories from last to first
            for ($i = count($list) - 1; $i >= 0; $i--) {
                // ----- Look for current path
                if ($list[$i] == ".") {
                    // ----- Ignore this directory
                    // Should be the first $i=0, but no check is done
                } elseif ($list[$i] == "..") {
                    // ----- Ignore it and ignore the $i-1
                    $i--;
                } elseif (($list[$i] == '')
                    && ($i != (count($list) - 1))
                    && ($i != 0)) {
                    // ----- Ignore only the double '//' in path,
                    // but not the first and last /
                } else {
                    $path = $list[$i].($i != (count($list) - 1) ? '/'.$path : '');
                }
            }
        }
        $path = strtr($path, '\\', '/');

        return $path;
    }

    protected function translateWinPath($path, $remove_disk_letter = true)
    {
        if (defined('OS_WINDOWS') && OS_WINDOWS) {
            // ----- Look for potential disk letter
            if (($remove_disk_letter)
                && (($position = strpos($path, ':')) != false)) {
                $path = substr($path, $position + 1);
            }
            // ----- Change potential windows directory separator
            if ((strpos($path, '\\') > 0) || (substr($path, 0, 1) == '\\')) {
                $path = strtr($path, '\\', '/');
            }
        }

        return $path;
    }

    /**
     * Detect and report a malicious file name
     *
     * @param string $file
     * @return bool
     * @access private
     */
    protected function maliciousFilename($file)
    {
        if (strpos($file, '/../') !== false) {
            return true;
        }
        if (strpos($file, '../') === 0) {
            return true;
        }

        return false;
    }

    private function isArchive($filename = null)
    {
        if ($filename == null) {
            $filename = $this->path;
        }
        clearstatcache();

        return @is_file($filename) && !@is_link($filename);
    }

    /**
     * @param $message
     * @param null $_
     * @return string
     */
    protected function error($message, $_ = null)
    {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $error = @vsprintf(array_shift($args), $args);
        } else {
            $error = $message;
        }
        $this->errors[] = $error;

        return $error;
    }

    /**
     * @param $message
     * @param null $_
     * @throws Exception
     */
    protected function warning($message, $_ = null)
    {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $warning = @vsprintf(array_shift($args), $args);
        } else {
            $warning = $message;
        }
        $this->warnings[] = $warning;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Used for big tars
     * @param int $len
     **/
    private function fseek($len)
    {
        @fread($this->file, $len * self::BLOCK_SIZE);
    }
}
