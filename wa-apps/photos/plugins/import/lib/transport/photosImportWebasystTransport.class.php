<?php

class photosImportWebasystTransport extends photosImportTransport
{

    protected $type = 1;
    protected $albums = array();
    protected $widgets = array();
    protected $photos = array();
    protected $old_photos = array();
    protected $contacts = array();

    protected $current_album;

    /**
     * @var waModel
     */
    protected $source;
    protected $source_path;

    /**
     * @var photosAlbumModel
     */
    protected static $album_model;
    /**
     * @var photosPhotoModel
     */
    protected static $photo_model;
    /**
     * @var photosPhotoExifModel
     */
    protected static $exif_model;


    public function initOptions()
    {
        $this->options['host'] = array(
            'title' => _wp('MySQL Host'),
            'value'=>'localhost',
            'settings_html_function'=>waHtmlControl::INPUT,
        );
        $this->options['user'] = array(
            'title' => _wp('MySQL User'),
            'value'=>'',
            'settings_html_function'=>waHtmlControl::INPUT,
        );
        $this->options['password'] = array(
            'title' => _wp('MySQL Password'),
            'value'=>'',
            'settings_html_function'=>waHtmlControl::PASSWORD,
        );
        $this->options['database'] = array(
            'title' => _wp('MySQL Database'),
            'value'=>'webasyst',
            'settings_html_function'=>waHtmlControl::INPUT,
        );
        $this->options['path'] = array(
            'title' => _wp('Path to folder'),
            'value'=> wa()->getConfig()->getRootPath(),
            'description' =>_wp('Path to folder <strong>data/[DBNAME]/attachments/pd</strong> of the WebAsyst Photos (old version) installation'),
            'settings_html_function'=>waHtmlControl::INPUT,
        );
    }

    public function init()
    {
        $this->source = new waModel(array(
            'host' => $this->options['host']['value'],
            'user' => $this->options['user']['value'],
            'password' => $this->options['password']['value'],
            'database' => $this->options['database']['value'],
        ));
        $this->source_path = $this->options['path']['value'];
        if (substr($this->source_path, -1) != '/') {
            $this->source_path .= '/';
        }
        if (!file_exists($this->source_path) || strpos($this->source_path, 'published/publicdata') !== false) {
            throw new waException(sprintf('Invalid PATH %s', $this->source_path));
        }
    }

    public function __wakeup()
    {
        parent::__wakeup();
        $this->source = new waModel(array(
            'host' => $this->options['host']['value'],
            'user' => $this->options['user']['value'],
            'password' => $this->options['password']['value'],
            'database' => $this->options['database']['value'],
        ));
    }

    public function count()
    {
        $this->albums = $this->getAlbums();
        $this->widgets = $this->getWidgets();
        $n =  count($this->albums) + count($this->widgets) + $this->countPhotos();
        $this->log($n);
        return $n;
    }


    public function step(&$current)
    {
        switch ($this->type) {
            // album
            case 1:
                $album_id = current($this->albums);
                $this->log('Current: '.$current);
                $this->current_album = $this->importAlbum($album_id);
                $current++;
                array_shift($this->albums);
                $this->photos = $this->getPhotos($album_id);
                if ($this->photos) {
                    $this->type = 2;
                } elseif (!$this->albums) {
                    $this->type = 3;
                }
                break;
            // photo
            case 2:
                $photo_id = current($this->photos);
                $this->log('Current: '.$current);
                $this->importPhoto($photo_id, $this->current_album);
                $current++;
                array_shift($this->photos);
                if (!$this->photos) {
                    // update count photos in album for current user
                    $sql = 'REPLACE photos_album_count
                    SET album_id = '.(int)$this->current_album.',
                        contact_id = '.wa()->getUser()->getId().',
                        count = (SELECT COUNT(*) FROM photos_album_photos ap WHERE ap.album_id = '.(int)$this->current_album.')';
                    $this->dest->exec($sql);
                    // set next step
                    $this->type = $this->albums ? 1 : 3;
                }
                break;
            // widget
            case 3:
                if ($this->widgets) {
                    $widget_id = current($this->widgets);
                    $this->log('Current: '.$current);
                    $this->importWidget($widget_id);
                    $current++;
                    array_shift($this->widgets);
                }
                break;
        }
    }

    protected function getContactId($contact_id)
    {
        if (isset($this->contacts[$contact_id])) {
            return $this->contacts[$contact_id];
        }
        $row = $this->query("SELECT C_EMAILADDRESS FROM CONTACT WHERE C_ID = ".(int)$contact_id);
        $email = $row ? $row['C_EMAILADDRESS'] : false;
        if ($email) {
            $sql = "SELECT contact_id FROM wa_contact_emails WHERE email = s:email ORDER BY sort LIMIT 1";
            $result = $this->dest->query($sql, array('email' => $email))->fetchField();
            if ($result) {
                return $this->contacts[$contact_id] = $result;
            }
        }
        $this->contacts[$contact_id] = !empty($this->options['contact_id']['value']) ? $this->options['contact_id']['value'] : wa()->getUser()->getId();
        return $this->contacts[$contact_id];
    }

    protected function query($sql, $one = true)
    {
        $q = $this->source->query($sql);
        if ($one) {
            return $q->fetch();
        } else {
            return $q->fetchAll();
        }
    }

    protected function getAlbumModel()
    {
        if (!self::$album_model) {
            self::$album_model = new photosAlbumModel();
        }
        return self::$album_model;
    }

    protected function getPhotoModel()
    {
        if (!self::$photo_model) {
            self::$photo_model = new photosPhotoModel();
        }
        return self::$photo_model;
    }

    protected function getExifModel()
    {
        if (!self::$exif_model) {
            self::$exif_model = new photosPhotoExifModel();
        }
        return self::$exif_model;
    }


    public function getAlbums()
    {
        $rows = $this->query("SELECT PF_ID FROM PIXFOLDER", false);
        $data = array();
        foreach ($rows as $r) {
            $data[] = $r['PF_ID'];
        }
        $this->log('Albums: '.print_r($data, true));
        return $data;
    }

    public function getWidgets()
    {
        $rows = $this->query("SELECT WG_ID FROM WG_WIDGET WHERE WT_ID = 'PDList'", false);
        $data = array();
        foreach ($rows as $r) {
            $data[] = $r['WG_ID'];
        }
        $this->log('Widgets: '.print_r($data, true));
        return $data;
    }

    public function getPhotos($album_id)
    {
        $rows = $this->query("SELECT PL_ID FROM PIXLIST WHERE PF_ID = ".(int)$album_id, false);
        $data = array();
        foreach ($rows as $r) {
            $data[] = $r['PL_ID'];
        }
        $this->log('Photos: '.print_r($data, true));
        return $data;
    }

    public function countPhotos()
    {
        $result = $this->query("SELECT COUNT(*) n FROM PIXLIST");
        return $result ? $result['n'] : 0;
    }

    public function importAlbum($id)
    {
        $this->log('Import album: '.$id);
        $row = $this->query("SELECT * FROM PIXFOLDER WHERE PF_ID = ".(int)$id);
        $data = array(
            'name' => $row['PF_NAME'],
            'parent_id' => 0,
            'create_datetime' => $row['PF_CREATEDATETIME'],
            'sort' => $row['PF_SORT'],
            'contact_id' => $this->getContactId($row['C_ID']),
            'status' => $row['PF_STATUS'] == 1 ? 1 : 0,
            'url' => $row['PF_LINK'] ? $row['PF_LINK'] : null,
            'note' => $row['PF_DATESTR'],
        );
        if (!$data['status']) {
            $data['hash'] = md5(uniqid(time(), true));
        }
        $data['full_url'] = $data['url'];
        $album_id = $this->getAlbumModel()->insert($data);
        $sql = "INSERT INTO photos_album_rights SET group_id = 0, album_id = ".(int)$album_id;
        $this->dest->exec($sql);
        if (!$data['status']) {
            $sql = "INSERT IGNORE INTO photos_album_rights
                    SET group_id = -".(int)$data['contact_id'].", album_id = ".(int)$album_id;
            $this->dest->exec($sql);
        }
        return array('id' => $album_id, 'status' => $data['status']);
    }

    public function importPhoto($id, $album)
    {
        $this->log('Import photo: '.$id);
        $row = $this->query("SELECT * FROM PIXLIST WHERE PL_ID = ".(int)$id);
        $data = array(
            'name' => preg_replace('/\.[^\.]+$/', '' , $row['PL_FILENAME']),
            'description' => $row['PL_DESC'],
            'upload_datetime' => $row['PL_UPLOADDATETIME'],
            'width' => $row['PL_WIDTH'],
            'height' => $row['PL_HEIGHT'],
            'size' => $row['PL_FILESIZE'],
            'ext' => waFiles::extension($row['PL_DISKFILENAME']),
            'contact_id' => $this->getContactId($row['C_ID']),
            'status' => $album['status'] ? 1 : 0
        );
        if ($data['status'] <= 0) {
            $data['hash'] = md5(uniqid(time(), true));
        } else {
            $data['hash'] = '';
        }
        // insert photo
        $data['id'] = $this->getPhotoModel()->insert($data);
        // set url
        $this->getPhotoModel()->updateById($data['id'], array(
            'url' => 'DSC_' . $data['id']
        ));
        // copy file
        $new_path = photosPhoto::getPhotoPath($data);
        $this->moveFile($row, $new_path);

        // fix width and height for old photos
        if (!$data['width'] && !$data['height'] && file_exists($new_path)) {
            $image = waImage::factory($new_path);
            $this->getPhotoModel()->updateById($data['id'], array(
                'width' => $image->width,
                'height' => $image->height,
            ));
        }

        if ($exif_data = photosExif::getInfo($new_path)) {
            $this->getExifModel()->save($data['id'], $exif_data);
        }
        // set rights
        $sql = "INSERT IGNORE INTO photos_photo_rights SET group_id = 0, photo_id = ".(int)$data['id'];
        $this->dest->exec($sql);
        if (!$album['status']) {
            $sql = "INSERT IGNORE INTO photos_photo_rights
                    SET group_id = -".(int)$data['contact_id'].", photo_id = ".(int)$data['id'];
            $this->dest->exec($sql);
        }
        // add photo to album
        $sql = "INSERT IGNORE INTO photos_album_photos
                SET album_id = ".$album['id'].", photo_id = ".(int)$data['id'].", sort = ".(int)$row['PL_SORT'];
        $this->dest->exec($sql);

        // save old id => new id (for widgets)
        $this->old_photos[$id] = $data['id'];

        return $data['id'];
    }


    protected function moveFile($row, $new_path)
    {
        $old_path = $this->source_path.'files/'.$row['PF_ID'].'/'.$row['PL_DISKFILENAME'];
        // some files in windows-1251
        if (!file_exists($old_path)) {
            $old_path = $this->source_path.'files/'.$row['PF_ID'].'/'.iconv("UTF-8", "WINDOWS-1251", $row['PL_DISKFILENAME']);
        }
        $this->log($old_path);
        $this->log($new_path);
        copy($old_path, $new_path);
    }

    public function importWidget($id)
    {
        $this->log('Import widget: '.$id);
        $row = $this->query("SELECT w.*, u.C_ID FROM WG_WIDGET w LEFT JOIN WBS_USER u ON w.WG_USER = u.U_ID WHERE WG_ID = ".(int)$id);
        $rows = $this->query("SELECT WGP_NAME, WGP_VALUE FROM WG_PARAM WHERE WG_ID = ".(int)$id, false);
        $params = array();
        foreach ($rows as $r) {
            $params[$r['WGP_NAME']] = $r['WGP_VALUE'];
        }

        $data = array(
            'url' => $row['WG_FPRINT'],
            'full_url' => $row['WG_FPRINT'],
            'name' => $row['WG_DESC'],
            'create_datetime' => $row['WG_CREATED_DATETIME'],
            'parent_id' => 0,
            'status' => 0,
            'sort' => ((int)$this->dest->query("SELECT MAX(sort) FROM photos_album")->fetchField() + 1),
            'contact_id' => $this->getContactId($row['C_ID'])
        );
        // insert album
        $album_id = $this->getAlbumModel()->insert($data);
        // set rights
        $sql = "INSERT INTO photos_album_rights SET group_id = 0, album_id = ".(int)$album_id;
        $this->dest->exec($sql);
        // add photos to album
        $ids = explode(",", $params['FILES']);
        $values = array();
        $sort = 0;
        foreach ($ids as $id) {
            if (isset($this->old_photos[$id])) {
                $values[] = "(".$album_id.", ".$this->old_photos[$id].", ".($sort++).")";
            }
        }
        if ($values) {
            $sql = "INSERT INTO photos_album_photos (album_id, photo_id, sort) VALUES ".implode(", ", $values);
            $this->dest->exec($sql);
        }
    }


}