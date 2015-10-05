<?php

class photosPhotosWidget extends waWidget
{
    public function defaultAction()
    {
        $collection = new photosCollection();
        $photos = array_values($collection->getPhotos("*,thumb_mobile", 0, 100));
        $this->display(array(
            'photos' => $photos,
            'uniqid' => uniqid(),
            'widget_id' => $this->id,
            'widget_url' => $this->getStaticUrl(),
            'photo_on_widget' => $this->getPhotoCountOnWidget(),
            'json_photos' => $this->getJSONPhotos($photos),
        ));
    }

    public function getPhotoCountOnWidget()
    {
        $size = explode("x", $this->info['size'], 2);
        $widget_width = $size[0];
        $widget_height = $size[1];
        $widget_area = ( $widget_width * $widget_height );
        $photo_on_widget = 1;
        if ($widget_area == 2) {
            $photo_on_widget = 2;
        }

        if ($widget_area == 4) {
            $photo_on_widget = 4;
        }
        return $photo_on_widget;
    }

    public function getJSONPhotos($photos)
    {
        $wa_photos_url = wa()->getAppUrl('photos');
        $result_array = array();
        foreach ($photos as $photo) {
            $result_array[] = array(
                "image_href" => $photo['thumb_mobile']['url'],
                "link_href" => $wa_photos_url.'#/photo/'.$photo['id'].'/',
            );
        }
        return json_encode($result_array);
    }
}