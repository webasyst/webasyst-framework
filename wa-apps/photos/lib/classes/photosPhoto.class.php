<?php

class photosPhoto
{
    const AUTHOR_PHOTO_SIZE = 32;
    const SHARP_AMOUNT = 6;

    public static function getPhotoUrl($photo, $size = null, $absolute = false, $cdn = null)
    {
        if (!$size) {
            $size = 970;
        }
        $path = self::getPhotoFolder($photo['id']).'/'.$photo['id'];
        if ($photo['status'] <= 0 && !empty($photo['hash'])) {
            $path .= '.'.$photo['hash'];
        }
        $path .= '/'.$photo['id'].'.'.($size ?  $size.'.' : '').$photo['ext'];

        $cdn = ifset($cdn, wa('photos')->getConfig()->getCDN());
        if ($cdn) {
            return $cdn.wa()->getDataUrl($path, true, 'photos', false);
        }

        if (waSystemConfig::systemOption('mod_rewrite')) {
            return wa()->getDataUrl($path, true, 'photos', $absolute);
        } else {
            $wa = wa();
            if (file_exists($wa->getDataPath($path, true, 'photos'))) {
                return $wa->getDataUrl($path, true, 'photos', $absolute);
            } else {
                return $wa->getDataUrl('thumb.php/'.$path, true, 'photos', $absolute);
            }
        }
    }

    public static function getPhotoUrlTemplate($photo, $absolute = false, $cdn = null)
    {
        $path = self::getPhotoFolder($photo['id']).'/'.$photo['id'];
        if ($photo['status'] <= 0 && !empty($photo['hash'])) {
            $path .= '.'.$photo['hash'];
        }
        $path .= '/'.$photo['id'].'.%size%.'.$photo['ext'];

        $cdn = ifset($cdn, wa('photos')->getConfig()->getCDN());
        if ($cdn) {
            return $cdn.wa()->getDataUrl($path, true, 'photos', false);
        } else if (waSystemConfig::systemOption('mod_rewrite')) {
            return wa()->getDataUrl($path, true, 'photos', $absolute);
        } else {
            return wa()->getDataUrl('thumb.php/'.$path, true, 'photos', $absolute);
        }
    }

    public static function getPhotoThumbDir($photo)
    {
        $path = self::getPhotoFolder($photo['id']).'/'.$photo['id'];
        if ($photo['status'] <= 0 && !empty($photo['hash'])) {
            $path .= '.'.$photo['hash'].'/';
        }
        return wa()->getDataPath($path, true, 'photos');
    }

    public static function getPhotoThumbPath($photo, $size)
    {
        $thumb_path = self::getPhotoThumbDir($photo);
        return $thumb_path.'/'.$photo['id'].'.'.$size.'.'.$photo['ext'];
    }

    private static function getPhotoFolder($photo_id)
    {
        $str = str_pad($photo_id, 4, '0', STR_PAD_LEFT);
        return substr($str, -2).'/'.substr($str, -4, 2);
    }

    public static function getPhotoPath($photo)
    {
        $file_name = self::getPhotoFolder($photo['id']).'/'.$photo['id'];
        if ($photo['status'] <= 0 && !empty($photo['hash'])) {
            $file_name .= '.'.$photo['hash'];
        }
        $file_name .= '.'.$photo['ext'];
        return wa()->getDataPath($file_name, false, 'photos');
    }

    /**
     * @static
     * @param array|string $photo
     * @return string
     */
    public static function getOriginalPhotoPath($photo)
    {
        if (is_array($photo)) {
            $file = self::getPhotoPath($photo);
        } else {
            $file = $photo;
        }
        if (($i = strrpos($file, '.')) !== false) {
            return substr($file, 0, $i).'.original'.substr($file, $i);
        } else {
            return $file.'.original';
        }
    }

    public static function generateThumbs($photo, $sizes = array())
    {
        $photo_path = self::getPhotoPath($photo);
        $apply_sharp = wa('photos')->getConfig()->getOption('sharpen');

        $main_thumbnail_size = photosPhoto::getBigPhotoSize();
        $main_thumbnail_path = self::getPhotoThumbPath($photo, $main_thumbnail_size);

        $quality = wa('photos')->getConfig()->getSaveQuality();

        foreach ((array)$sizes as $size) {
            if ($size == $main_thumbnail_size) {
                continue;
            }
            $image = self::generateThumb(array(
                    'path' => $main_thumbnail_path,
                    'size' => $main_thumbnail_size
                ),
                $photo_path,
                $size,
                $apply_sharp
            );
            if (!$image) {
                continue;
            }
            $image->save(self::getPhotoThumbPath($photo, $size), $quality);
        }

        // sharp for mail thumbnail
        if ($apply_sharp) {
            $image = waImage::factory($main_thumbnail_path);
            $image->sharpen(self::SHARP_AMOUNT);
            $image->save(null, $quality);
        }
        clearstatcache();
    }

    protected static function _generateThumb($path, $type, $width, $height, $sharpen = false)
    {
        $image = null;
        switch($type) {
            case 'crop':
                if ($path instanceof waImage) {
                    $image = $path;
                } else {
                    $image = waImage::factory($path);
                }
                $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
                break;
            case 'rectangle':
                if ($path instanceof waImage) {
                    $image = $path;
                } else {
                    $image = waImage::factory($path);
                }
                if ($width > $height) {
                    $w = $image->width;
                    $h = $image->width*$height/$width;
                    if ($h > $image->height) {
                        $h = $image->height;
                        $w = $image->height*$width/$height;
                    }
                } else {
                    $h = $image->height;
                    $w = $image->height*$width/$height;
                    if ($w > $image->width) {
                        $w = $image->width;
                        $h = $image->width*$height/$width;
                    }
                }
                $image->crop($w, $h)->resize($width, $height, waImage::INVERSE);
                break;
            case 'max':
            case 'width':
            case 'height':
                if ($path instanceof waImage) {
                    $image = $path;
                } else {
                    $image = waImage::factory($path);
                }
                $image->resize($width, $height);
                break;
            default:
                break;
        }
        if ($sharpen) {
            $image->sharpen(photosPhoto::SHARP_AMOUNT);
        }
        return $image;
    }

    public static function generateThumb($main_thumbnail_info, $original_path, $size, $sharpen = false, $max_size = false)
    {
        $main_thumbnail_path = $main_thumbnail_info['path'];
        $main_thumbnail_size = $main_thumbnail_info['size'];
        if (!file_exists($main_thumbnail_path)) {
            $size_info = photosPhoto::parseSize($main_thumbnail_size);
            $type = $size_info['type'];
            $width = $size_info['width'];
            $height = $size_info['height'];
            if ($image = self::_generateThumb($original_path, $type, $width, $height)) {
                $image->save($main_thumbnail_path);
            }
            $main_thumbnail_width = $image->width;
            $main_thumbnail_height = $image->height;
        } else {
            $image = waImage::factory($main_thumbnail_path);
            $main_thumbnail_width = $image->width;
            $main_thumbnail_height = $image->height;
        }

        $path = $main_thumbnail_path;
        $width = $height = null;
        $size_info = photosPhoto::parseSize($size);
        $type = $size_info['type'];
        $width = $size_info['width'];
        $height = $size_info['height'];

        if (!$width && !$height) {
            return null;
        }

        switch($type) {
            case 'max':
                if (is_numeric($max_size) && $width > $max_size) {
                    return null;
                }
                if ($width > max($main_thumbnail_width, $main_thumbnail_height)) {
                    $image = waImage::factory($original_path);  // make thumb from original photo
                }
                break;
            case 'crop':
                if (is_numeric($max_size) && $width > $max_size) {
                    return null;
                }
            case 'rectangle':
                if (is_numeric($max_size) && ($width > $max_size || $height > $max_size)) {
                    return null;
                }
                if ($width > $main_thumbnail_width || $height > $main_thumbnail_height) {
                    $image = waImage::factory($original_path);  // make thumb from original photo
                }
                break;
            case 'width':
                $w = !is_null($width) ? $width : $height;
                $original_image = waImage::factory($original_path);
                $h = $original_image->height * ($w/$original_image->width);
                $w = min(round($w), $original_image->width);
                $h = min(round($h), $original_image->height);
                if ($w == $main_thumbnail_width && $h == $main_thumbnail_height) {
                    return $image;
                }
                if (is_numeric($max_size) && ($w > $max_size || $h > $max_size)) {
                    return null;
                }
                if ($w > $main_thumbnail_width || $h > $main_thumbnail_height) {
                    $image = $original_image;  // make thumb from original photo
                }
                break;
            case 'height':
                $h = !is_null($width) ? $width : $height;
                $original_image = waImage::factory($original_path);
                $w = $original_image->width * ($h/$original_image->height);
                $w = min(round($w), $original_image->width);
                $h = min(round($h), $original_image->height);
                if ($w == $main_thumbnail_width && $h == $main_thumbnail_height) {
                    return $image;
                }
                if (is_numeric($max_size) && ($w > $max_size || $h > $max_size)) {
                    return null;
                }
                if ($w > $main_thumbnail_width || $h > $main_thumbnail_height) {
                    $image = $original_image;  // make thumb from original photo
                }
                break;
            default:
                $type = 'unknown';
                break;
        }
        return self::_generateThumb($image, $type, $width, $height, $sharpen);
    }

    public static function escapeFields($photo)
    {
        // escape
        $photo['name'] = photosPhoto::escape($photo['name']);
//         $photo['description'] = photosPhoto::escape($photo['description']);
        $photo['url'] = photosPhoto::escape($photo['url']);
        return $photo;
    }

    /**
     * Parsing size-code (e.g. 500x400, 500, 96x96, 200x0) into key-value array with info about this size
     *
     * @see Client-side has the same function with the same realization
     * @param string $size
     * @returns array
     */
    public static function parseSize($size)
    {
        $type = 'unknown';
        $ar_size = explode('x', $size);
        $width = !empty($ar_size[0]) ? $ar_size[0] : null;
        $height = !empty($ar_size[1]) ? $ar_size[1] : null;

        if (count($ar_size) == 1) {
            $type = 'max';
            $height = $width;
        } else {
            if ($width == $height) {  // crop
                $type = 'crop';
            } else {
                if ($width && $height) { // rectange
                    $type = 'rectangle';
                } else if (is_null($width)) {
                    $type = 'height';
                } else if (is_null($height)) {
                    $type = 'width';
                }
            }
        }
        return array(
            'type' => $type,
            'width' => $width,
            'height' => $height
        );
    }

    /**
     * Calculate real size of photo thumbnail
     *
     * @see Client-side has the same function with the same realization
     * @param array $photo Key-value object with photo info
     * @param string $size string size-code or key-value object returned by parseSize
     * @returns array Key-value object with width and height values
     */
    public static function getRealSizesOfThumb($photo, $size = null)
    {
        if (!$photo['width'] && !$photo['height']) {
            return null;
        }
        $size = !is_null($size) ? $size : self::getThumbPhotoSize();
        $rate = $photo['width']/$photo['height'];
        $revert_rate = $photo['height']/$photo['width'];

        if (!is_array($size)) {
            $size_info = photosPhoto::parseSize($size);
        } else {
            $size_info = $size;
        }
        $type = $size_info['type'];
        $width = $size_info['width'];
        $height = $size_info['height'];
        switch($type) {
            case 'max':
                if ($photo['width'] > $photo['height']) {
                    $w = $width;
                    $h = $revert_rate*$w;
                } else {
                    $h = $width; // second param in case of 'max' type has size of max side, so width is now height
                    $w = $rate*$h;
                }
                break;
            case 'crop':
                $w = $h = $width; // $width == $height
                break;
            case 'rectangle':
                $w = $width;
                $h = $height;
                break;
            case 'width':
                $w = $width;
                $h = $revert_rate*$w;
                break;
            case 'height':
                $h = $height;
                $w = $rate*$h;
                break;
            default:
                $w = $h = null;
                break;
        }
        $w = round($w);
        $h = round($h);
        if ($photo['width'] < $w && $photo['height'] < $h) {
            return array(
                'width' => $photo['width'],
                'height' => $photo['height']
            );
        }
        return array(
            'width' => $w,
            'height' => $h
        );
    }

    public static function getThumbInfo($photo, $size, $absolute = true)
    {
        $size_info = photosPhoto::parseSize($size);
        return array(
            'size' => photosPhoto::getRealSizesOfThumb($photo, $size_info),
            'url' => photosPhoto::getPhotoUrl($photo, $size, $absolute, wa('photos')->getConfig()->getCDN()),
            'bound' => array(
                'width' => $size_info['width'],
                'height' => $size_info['height']
        ));
    }

    public static function getEmbedImgHtml($photo, $size, $attributes = array(), $style = true, $absolute = true, $cdn = null)
    {
        if ($photo['width'] && $photo['height']) {

            $real_sizes = photosPhoto::getRealSizesOfThumb($photo, $size);
            if ($real_sizes && $real_sizes['width'] && $real_sizes['height'] && $style) {
                $attributes['style'] = !empty($attributes['style']) ? $attributes['style'] : '';
                $attributes['style'] .= 'width: '.(int)$real_sizes['width'].'px; height: '.(int)$real_sizes['height'].'px; ';
                $attributes['width'] = (int) $real_sizes['width'];
                $attributes['height'] = (int) $real_sizes['height'];
            }
        }
        if (!isset($attributes['alt'])) {
            $attributes['alt'] = '';
        }
        $photo['src'] = photosPhoto::getPhotoUrl($photo, $size, $absolute, ifset($cdn, wa('photos')->getConfig()->getCDN()));
        if ($photo['edit_datetime']) {
            $photo['src'] .= '?'.strtotime($photo['edit_datetime']);
        }
        $attr = '';
        foreach ($attributes as $name => $value) {
            if (preg_match('/^%(.+)%$/', $value, $matches) && isset($photo[$matches[1]])) {
                $value = $photo[$matches[1]];
            }
            $value = htmlentities($value, ENT_QUOTES, 'utf-8');
            $attr .= $name.'="'.$value.'" ';
        }
        return "<img src=\"{$photo['src']}\" {$attr}>";   // use everywhere only one type of quotes
    }

    public static function getEmbedPhotoListContext($hash, $size, $limit = null, $context = null)
    {
        $link = photosCollection::getFrontendLink($hash);
        $collection = new photosCollection($hash);
        $thumb_key = 'thumb_'.$size;
        $photos = $collection->getPhotos('*, '.$thumb_key, 0, $limit == null ? 500 : $limit);

        $photo_ids = array_keys($photos);
        $photo_model = new photosPhotoModel();
        $public_photo_ids = $photo_model->filterByField($photo_ids, 'status', 1);
        $all_public = count($photo_ids) == count($public_photo_ids);

        // change default collection sort if hash looks like id/1,2,3,4
        if (strstr($hash, 'id/') !== false) {
            $photo_ids = explode(',', preg_replace('/\/*id\//', '', $hash));
            $old_photos = $photos;
            $photos = array();
            foreach ($photo_ids as $photo_id) {
                $photo_id = intval($photo_id);        // need in case of private photo (cause of hash suffix)
                if (isset($old_photos[$photo_id])) {
                    $photos[$photo_id] = $old_photos[$photo_id];
                }
            }
        }

        $urls = '';
        $html = '';
        $html_with_descriptions = '';
        foreach ($photos as $photo) {
            $urls .= $photo[$thumb_key]['url'].PHP_EOL;
            $img_html = photosPhoto::getEmbedImgHtml($photo, $size);
            $html_with_descriptions .= '<p>' . ($photo['description'] ? $photo['description'].'<br>' : '') . $img_html.'</p>'.PHP_EOL;
            $html .= $img_html.'<br>'.PHP_EOL;
        }

        $params_string = '"'.$hash.'", "'.$size.'"';
        $smarty_code = '{if $wa->photos}'.PHP_EOL;
        $smarty_code .= '    {$photos = $wa->photos->photos('.$params_string.')}'.PHP_EOL;
        $smarty_code .= '    {foreach $photos as $photo}'.PHP_EOL;
        $smarty_code .= '        <p>{if $photo.description}{$photo.description}<br>{/if}'.PHP_EOL;
        $smarty_code .= '            <img src=\'{$photo.'.$thumb_key.'.url}\' alt=\'{$photo.name}.{$photo.ext}\'>'.PHP_EOL;
        $smarty_code .= '        </p>'.PHP_EOL;
        $smarty_code .= '    {/foreach}'.PHP_EOL;
        $smarty_code .= '{/if}'.PHP_EOL;

        return array(
            'urls' => $urls,
            'html' => $html,
            'html_with_descriptions' => $html_with_descriptions,
            'link' => $link,
            'smarty_code' => $smarty_code,
            'count' => count($photos),
            'all_public' => $all_public,
            'domains' => self::getDomains($hash),
        );
    }

    /** Description of all framework settlements. */
    public static function getDomains($hash, $photo=null)
    {
        $domains = array();

        // Params for getRouteUrl() based on $hash or $photo
        if ($photo) {
            $route_module_action = 'photos/frontend/photo';
            $route_url_params = array(
                'url' => $photo['url'].(isset($photo['status']) && ($photo['status'] <= 0 && !empty($photo['hash'])) ? ':'.$photo['hash'] : '')
            );
        } else {
            $route_module_action = 'photos/frontend';
            $route_url_params = array();
            $hash = trim($hash, '#/');
            $hash = explode('/', $hash);
            if (count($hash) >= 2) {
                if ($hash[0] == 'album') {
                    $route_module_action = 'photos/frontend/album';
                    $route_url_params = array(
                        'url' => photosCollection::frontendAlbumHashToUrl('album/'.$hash[1]),
                    );
                } else {
                    $route_url_params[$hash[0]] = $hash[1];
                }
            } else if (count($hash) == 1) {
                $route_url_params[$hash[0]] = $hash[0];
            }
        }

        $schema = waRequest::isHttps() ? 'https://' : 'http://';

        // Domains with Photos frontend
        foreach(wa()->getRouting()->getByApp('photos') as $domain => $params) {
            $domain_url = $schema . rtrim(wa()->getRouting()->getDomainUrl($domain), '/').'/';
            $domains[$domain_url] = array(
                'url' => $domain_url,
                'frontend_url' => wa()->getRouteUrl($route_module_action, $route_url_params, true, $domain),
            );
        }

        // Other domains
        foreach(array_merge(wa()->getRouting()->getDomains(), array(waRequest::server('HTTP_HOST').wa()->getConfig()->getRootUrl())) as $domain) {
            $domain_url = $schema . rtrim(wa()->getRouting()->getDomainUrl($domain), '/').'/';
            if (empty($domains[$domain_url])) {
                $domains[$domain_url] = array(
                    'url' => $domain_url,
                    'frontend_url' => '',
                );
            }
        }

        return $domains;
    }

    public static function getBigPhotoSize()
    {
        return wa('photos')->getConfig()->getSize('big');
    }

    public static function getMiddlePhotoSize()
    {
        return wa('photos')->getConfig()->getSize('middle');
    }

    public static function getThumbPhotoSize()
    {
        return wa('photos')->getConfig()->getSize('thumb');
    }

    public static function getCropPhotoSize()
    {
        return wa('photos')->getConfig()->getSize('crop');
    }

    public static function getMobilePhotoSize()
    {
        return wa('photos')->getConfig()->getSize('mobile');
    }

    public static function getRatingHtml($rating, $size = 10, $show_when_zero = false)
    {
        $rating = round($rating * 2) / 2;
        if (!$rating && !$show_when_zero) {
            return '';
        }
        $html = '';
        for ($i = 1; $i <= 5; $i += 1) {
            $html .= '<i class="icon'.$size.' star';
            if ($i > $rating) {
                if ($i - $rating == 0.5) {
                    $html .= '-half';
                } else {
                    $html .= '-empty';
                }
            }
            $html .= '"></i>';
        }
        return $html;
    }

    public static function escape($data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                $item = self::escape($item);
            }
            unset($item);
        } else {
            $data = htmlspecialchars($data);
        }
        return $data;
    }

    public static function sprintf_wplural()
    {
        $args = func_get_args();
        $w_args = array_splice($args, 0, 3);
        $str = call_user_func_array('_w', $w_args);
        array_unshift($args, $str);
        return call_user_func_array('sprintf', $args);
    }

    public static function suggestUrl($str)
    {
        $str = preg_replace('/\s+/', '-', $str);
        if ($str) {
            foreach (waLocale::getAll() as $lang) {
                $str = waLocale::transliterate($str, $lang);
            }
        }
        $str = preg_replace('/[^a-zA-Z0-9_-]+/', '', $str);
        if (!strlen($str)) {
            $str = date('Ymd');
        }
        return strtolower($str);
    }
}