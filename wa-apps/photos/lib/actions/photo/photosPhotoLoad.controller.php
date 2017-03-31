<?php

class photosPhotoLoadController extends waJsonController
{
    /**
     * @var array
     */
    private $photo;

    /**
     * @var photosPhotoModel
     */
    private $photo_model;

    public function execute()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $in_stack = waRequest::post('in_stack', 0, waRequest::TYPE_INT);
        $hash = waRequest::post('hash', null, waRequest::TYPE_STRING_TRIM);
        $hash = urldecode($hash);

        // get photo
        $this->photo_model = new photosPhotoModel();
        $this->photo = $this->photo_model->getById($id);
        if (!$this->photo) {
            throw new waException(_w("Photo doesn't exists"), 404);
        }

        $photo_rights_model = new photosPhotoRightsModel();
        if (!$photo_rights_model->checkRights($this->photo)) {
            throw new waRightsException(_w("You don't have sufficient access rights"));
        }

        $this->photo['name_not_escaped'] = $this->photo['name'];
        $this->photo = photosPhoto::escapeFields($this->photo);
        $this->photo['upload_datetime_formatted'] = waDateTime::format('humandate', $this->photo['upload_datetime']);
        $this->photo['upload_timestamp'] = strtotime($this->photo['upload_datetime']);
        $this->photo['edit_rights'] = $photo_rights_model->checkRights($this->photo, true);
        $this->photo['private_url'] = photosPhotoModel::getPrivateUrl($this->photo);

        $this->photo['thumb'] = photosPhoto::getThumbInfo($this->photo, photosPhoto::getThumbPhotoSize());
        $this->photo['thumb_big'] = photosPhoto::getThumbInfo($this->photo, photosPhoto::getBigPhotoSize());
        $this->photo['thumb_middle'] = photosPhoto::getThumbInfo($this->photo, photosPhoto::getMiddlePhotoSize());

        $original_photo_path = photosPhoto::getOriginalPhotoPath($this->photo);
        if (wa('photos')->getConfig()->getOption('save_original') && file_exists($original_photo_path)) {
            $this->photo['original_exists'] = true;
        } else {
            $this->photo['original_exists'] = false;
        }

        $photo_tags_model = new photosPhotoTagsModel();
        $tags = $photo_tags_model->getTags($id);
        $this->photo['tags'] = $tags;

        $this->response['photo'] = $this->photo;

        // get stack if it's possible
        if (!$in_stack &&
            $stack = $this->photo_model->getStack($id, array(
                'thumb' => true,
                'thumb_crop' => true,
                'thumb_big' => true,
                'thumb_middle' => true
            )))
        {
            $this->response['stack'] = $stack;
        }

        // get albums
        $album_photos_model = new photosAlbumPhotosModel();
        $albums = $album_photos_model->getAlbums($id, array('id', 'name'));
        $this->response['albums'] = isset($albums[$id]) ? array_values($albums[$id]) : array();

        // exif info
        $exif_model = new photosPhotoExifModel();
        $exif = $exif_model->getByPhoto($this->photo['id']);
        if (isset($exif['DateTimeOriginal'])) {
            $exif['DateTimeOriginal'] = waDateTime::format('humandatetime', $exif['DateTimeOriginal'], date_default_timezone_get());
        }
        $this->response['exif'] = $exif;

        // get author
        $contact =  new waContact($this->photo['contact_id']);
        try {
            $this->response['author'] = array(
                'id' => $contact['id'],
                'name' => photosPhoto::escape($contact['name']),
                'photo_url' => $contact->getPhoto(photosPhoto::AUTHOR_PHOTO_SIZE),
                'backend_url' => $this->getConfig()->getBackendUrl(true) . 'contacts/#/contact/' . $contact['id']
            );
        } catch (waException $e) {
            $this->response['author'] = array(
                'id' => $this->photo['contact_id'],
                'name' => '',
                'photo_url' => wa()->getRootUrl().'wa-content/img/userpic'.photosPhoto::AUTHOR_PHOTO_SIZE.'.jpg',
                'backend_url' => ''
            );
        }

        // for making inline-editable widget
        $this->response['frontend_link_template'] = photosFrontendPhoto::getLink(array(
            'url' => '%url%'
        ));


        $hooks = array();
        $parent_id = $this->photo_model->getStackParentId($this->photo);
        $photo_id = $parent_id ? $parent_id : $id;

        /**
         * Extend photo page
         * Add extra widget(s)
         * @event backend_photo
         * @return array[string][string]string $return[%plugin_id%]['bottom'] In bottom, under photo any widget
         */
        $hooks['backend_photo'] = wa()->event('backend_photo', $photo_id);
        $this->response['hooks'] = $hooks;

        if ($hash !== null) {
            $collection = new photosCollection($hash);
            if (strstr($hash, 'rate>0') !== false) {
                $collection->orderBy('p.rate DESC, p.id');
            }
            $this->response['photo_stream'] = $this->getPhotoStream($collection);
            if ($collection->getAlbum()) {
                $this->response['album'] = $collection->getAlbum();
            }
        }
    }

    public function getPhotoStream(photosCollection $collection)
    {
        $parent = $this->photo_model->getStackParent($this->photo);
        $current_photo = $parent ? $parent : $this->photo;
        $offset = $collection->getPhotoOffset($current_photo);
        $total_count = $collection->count();

        $found_photos = $collection->getPhotos("id", $offset, 1, false);
        $found_photo = reset($found_photos);
        $in_collection = $found_photo && $found_photo['id'] == $this->photo['id'];

        $count = $this->getConfig()->getOption('photos_per_page');
        $offset = max($offset - floor($count/2), 0);
        $photos = array_values($collection->getPhotos('*,thumb,thumb_crop,thumb_middle,thumb_big,tags,edit_rights', $offset, $count));

        return array(
            'total_count' => $total_count,
            'photos' => $photos,
            'current_photo_id' => $current_photo['id'],
            'in_collection' => $in_collection
        );
    }
}
