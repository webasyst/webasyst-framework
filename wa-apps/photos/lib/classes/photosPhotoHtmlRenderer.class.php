<?php

class photosPhotoHtmlRenderer
{
    /**
     * @var waTheme
     */
    private $theme;

    public function __construct(waTheme $theme)
    {
        $this->theme = $theme;
    }

    /**
     *
     * Render html block 'photo-stream' with json-data for RIA UI
     *
     * @param array $photo_stream. Array of photos
     * @param array $current_photo|null. Associative array represented current photo of null

     * @return string $html rendered html with 'photo-stream-cache' json data for RIA UI
     */
    public function getPhotoStream($photo_stream, $current_photo = null)
    {
        $theme_url = $this->theme->getUrl();

        $html = '<ul class="photostream" id="photo-stream">' .
                    '<li class="stream-nav rewind"><a href="javascript:void(0);"><i></i></a></li>' .
                    '<li class="stream-nav ff"><a href="javascript:void(0);"><i></i></a></li><li class="stream-wrapper"><ul class="photostream">';
        $photo_stream = array_values($photo_stream);

        $list = array();
        $current_index = 0;
        $data = array();
        foreach ($photo_stream as $i => $photo) {
            if (!is_null($photo)) {
                $data[] = $photo;
            }
            $li = &$list[$i];
            $class = 'class="';
            if ($current_photo && $current_photo['id'] == $photo['id']) {
                $class .= 'selected';
                $current_index = $i;
            }
            $class .= is_null($photo) ? 'dummy ' : '';
            $class .= '"';
            $li = "<li $class data-photo-id='".$photo['id']."'>";

            // hidden image with class thumb need for rich gradual loading photo effect when go next/prev photo
            if (is_null($photo)) {
                $li .= '<a href="javascript:void(0);"><img src="'.$theme_url.'/img/photostream-end.png"></a>';
            } else {
                $salt = !is_null($photo['edit_datetime']) ? '?'.strtotime($photo['edit_datetime']) : '';
                $li .= '<a href="'.(isset($photo['full_url']) ? $photo['full_url'] : $photo['url']).'"><img src="'.$photo['thumb_crop']['url'].$salt.'" alt=""><img class="thumb" src="'.$photo['thumb']['url'].$salt.'" style="display:none;" alt=""></a>';
            }
            $li .= '</li>';
        }

        if ($current_photo) {
            for ($i = max($current_index - 2, 0); $i < $current_index + 3; ++$i) {
                if (isset($list[$i])) {
                    $list[$i] = preg_replace('/<li class="([^=]*?)"/', '<li class="$1 visible"', $list[$i]);
                }
            }
        }

        $html .= implode('', $list);
        $html .= '</li></ul></ul>';

        $html .= '<script>';
        $html .= '    var __photo_stream_data = '.json_encode($data);
        $html .= '</script>';

        return $html;
    }

    /**
     *
     * Render html block 'stack-navigation-panel' with json-data for RIA UI
     *
     * @param array $stack. Array of photos
     * @param array $current_photo. Array represented current photo
     *
     * @return string $html rendered html with 'photo-stream-cache' json data for RIA UI
     */
    public function getStackNavigationPanel($stack, $current_photo)
    {
        $theme_url = $this->theme->getUrl();

        $current = 0;
        $count = count($stack);
        $prev_in_stack = null;
        $next_in_stack = null;
        foreach ($stack as $photo) {
            if ($photo['id'] == $current_photo['id']) {
                if ($current > 0) {
                    $prev_in_stack = $stack[$current - 1];
                    //$prev_in_stack['url'] = str_replace('%url%', $prev_in_stack['url'], $url_template);
                }
                if ($current < $count - 1) {
                    $next_in_stack = $stack[$current + 1];
                    //$next_in_stack['url'] = str_replace('%url%', $next_in_stack['url'], $url_template);
                }
                break;
            }
            $current++;
        }
        $offset = $current + 1;

        $html  = "<div class='stack-nav' data-photo-id='{$current_photo['id']}'>";
        $html .= '  <a href="'.($prev_in_stack ? (isset($prev_in_stack['full_url']) ? $prev_in_stack['full_url'] : $prev_in_stack['url']) : 'javascript:void(0);').'" class="rewind"><img src="'.$theme_url.'img/stack-rewind.png" alt=""></a>';
        $html .= "  <strong class='offset'>$offset</strong> / $count";
        $html .= '  <a href="'.($next_in_stack ? (isset($next_in_stack['full_url']) ? $next_in_stack['full_url'] : $next_in_stack['url']) : 'javascript:void(0);').'" class="ff"><img src="'.$theme_url.'img/stack-ff.png" "alt=""></a>';

        // hidden stack-stream with class thumb need for rich gradual loading photo effect when go next/prev photo in stack
        $html .= '  <ul class="photostream" style="display:none;">';
        for ($i = 0; $i < $count; ++$i) {
            $html .= "  <li data-photo-id='{$stack[$i]['id']}' ".($i == $current ? 'class="selected"' : '').">";
            $html .= "    <a href='{$stack[$i]['url']}'><img class='thumb' src='{$stack[$i]['thumb']['url']}". (!is_null($stack[$i]['edit_datetime']) ? '?'.strtotime($stack[$i]['edit_datetime']) : '')."' alt=''></a>";
            $html .= "  </li>";
        }
        $html .= '  </ul>';
        $html .= '</div>';

        $html .= '<script>';
        $html .= '    var __photo_stack_data = __photo_stack_data || {};';
        $html .= "    __photo_stack_data['{$current_photo['id']}'] = ".json_encode($stack);
        $html .= '</script>';

        return $html;
    }

    public function getAlbums($albums)
    {
        $html = '';
        $i = 0;
        foreach ($albums as $album) {
            $html .= ($i++ > 0 ? ', ' : '') . '<a href="'.photosFrontendAlbum::getLink($album).'">'.photosPhoto::escape($album['name']).'</a>';
        }
        return $html;
    }

    public function getTags($tags)
    {
        $html = '';
        $i = 0;
        $wa_app_url = wa()->getAppUrl(null, true);
        foreach ($tags as $name) {
            //$html .= ($i++ > 0 ? ', ' : '') . '<a href="'.$wa_app_url.'tag/'.urlencode($name).'">'.photosPhoto::escape($name).'</a>';
            $html .= ($i++ > 0 ? ', ' : '') . '<a href="'.$wa_app_url.'tag/'.urlencode($name).'">'.$name.'</a>';
        }
        return $html;
    }

    public function getExifInfo($exif)
    {
        $html = '';
        if (isset($exif['Model'])) {
            $html .= _w("Camera").": <strong>{$exif['Model']}</strong><br>";
        }

        $html .= isset($exif['ISOSpeedRatings']) ? _w("ISO").": <strong>{$exif['ISOSpeedRatings']}</strong><br>" : '';
        $html .= isset($exif['ExposureTime']) ? _w("Shutter").": <strong>{$exif['ExposureTime']}</strong><br>" : '';
        $html .= isset($exif['FNumber']) ? _w("Aperture").": <strong>{$exif['FNumber']}</strong><br>" : '';
        $html .= isset($exif['FocalLength']) ? _w("Focal length").": <strong>{$exif['FocalLength']}</strong><br>" : '';
        $html .= isset($exif['DateTimeOriginal']) ? _w("Taken").": <strong>" . waDateTime::format('humandatetime', $exif['DateTimeOriginal'], date_default_timezone_get()) . "</strong><br>" : '';

        $gps = '';
        if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
            $gps = 'data-lat='.$exif['GPSLatitude'].' '.'data-lng='.$exif['GPSLongitude'];
        }
        $html .= '<div class="p-map middle" id="photo-map" '.$gps.' style="display:none;"></div>';

        return $html;
    }

    public function getAuthorInfo($author)
    {
        $wa_app_url = wa()->getAppUrl(null, false);
        $datetime = waDateTime::format('humandatetime', $author['photo_upload_datetime']);
        $html = '<a href="'.$wa_app_url.'author/'.$author['id'].'/">'.photosPhoto::escape($author['name']).'</a> '
                    ._w('on').' '.$datetime;
        return $html;
    }
}