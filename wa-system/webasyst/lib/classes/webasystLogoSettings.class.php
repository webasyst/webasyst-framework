<?php

class webasystLogoSettings
{
    protected static $runtime_cache = [];

    protected $options;

    /**
     * webasystLogoSettings constructor.
     * @param array $options
     *      bool $options['absolute_urls'] [optional]. Default is false
     */
    public function __construct(array $options = [])
    {
        $options['absolute_urls'] = isset($options['absolute_urls']) ? boolval($options['absolute_urls']) : false;
        $this->options = $options;
    }

    /**
     * Get current logo settings
     * @return array $logo
     *      - string $logo['mode'] = 'gradient' | 'image'
     *      - string $logo['text']['value']
     *      - string $logo['text']['default_value']
     *      - string $logo['text']['color']
     *      - string $logo['text']['default_color']
     *
     *      - bool $logo['two_lines']
     *
     *      - string $logo['gradient']['from'] - hex code of color (without #)
     *      - string $logo['gradient']['to'] - hex code of color (without #)
     *      - int    $logo['gradient']['angle']
     *
     *      - array $logo['image']['thumbs']
     *      - array $logo['image']['original']
     * @throws waException
     */
    public function get()
    {
        $asm = new waAppSettingsModel();
        $logo = $asm->get('webasyst', 'logo');
        if ($logo) {
            $logo = json_decode($logo, true);
        }

        if (!$logo) {
            $logo = [];
        }

        if (empty($logo['mode']) || ($logo['mode'] != 'gradient' && $logo['mode'] != 'image')) {
            $logo['mode'] = 'gradient';
        }

        $logo['text'] = [
            'value'         => !empty($logo['text']['value']) ? $logo['text']['value'] : '',
            'color'         => !empty($logo['text']['color']) ? $logo['text']['color'] : '',
            'default_value' => '',
            'default_color' => '#fff'
        ];

        $logo['two_lines'] = !empty($logo['two_lines']);

        $logo['gradient'] = !empty($logo['gradient']) ? $logo['gradient'] : [];
        $logo['gradients'] = $this->getGradients();

        if (!$logo['gradient']) {
            $gradients = $logo['gradients'];
            $gradient = reset($gradients);
            $logo['gradient'] = $gradient;
        }

        $logo['image'] = [
            'thumbs' => !empty($logo['image']['thumbs']) ? $logo['image']['thumbs'] : [],
            'original' => !empty($logo['image']['original']) ? $logo['image']['original'] : [],
        ];

        foreach ($logo['image']['thumbs'] as &$thumb) {
            $thumb_url = wa()->getDataUrl($thumb['path'], true, 'webasyst', $this->options['absolute_urls']);
            $thumb['url'] = $thumb_url . '?ts=' . $thumb['ts'];
        }
        unset($thumb);

        if ($logo['image']['original']) {
            $logo['image']['original']['url'] = wa()->getDataUrl($logo['image']['original']['path'], true, 'webasyst', $this->options['absolute_urls']);
            $logo['image']['original']['url'] .= '?ts=' . $logo['image']['original']['ts'];
        }

        // if logo must be in two lines position and symbols more then 3
        $logo['text']['formatted_value'] = $logo['text']['value'];
        if ($logo['text']['value'] && $logo['two_lines'] && mb_strlen($logo['text']['value']) > 3) {
            $logo['text']['formatted_value'] = preg_replace("/\w{2}/u", "$0\n", $logo['text']['value']);
        }

        return $logo;
    }

    /**
     * @param array $logo - logo settings structure
     *      - string $logo['mode'] = 'gradient' | 'image'
     *      - string $logo['text']['value']
     *      - string $logo['text']['color']
     *
     *      - bool $logo['two_lines']
     *
     *      - string $logo['gradient']['from'] - hex code of color (without #)
     *      - string $logo['gradient']['to'] - hex code of color (without #)
     *      - int    $logo['gradient']['angle']
     *
     * @see getLogoSettings
     * @param bool $merge - if TRUE merge with existing, otherwise save as it
     */
    public function set(array $logo, $merge = true)
    {
        // to save image user method setLogoImage()
        unset($logo['image']);
        $this->setLogoSettings($logo, $merge);
    }

    private function setLogoSettings(array $logo, $merge = true)
    {
        if (!$logo && $merge) {
            return; // nothing changed - earlier exit
        }

        if ($merge) {
            $result = $this->get();
            foreach (['text', 'gradient', 'image'] as $key) {
                if (isset($logo[$key])) {
                    $result[$key] = array_merge($result[$key], $logo[$key]);
                    unset($logo[$key]);
                }
            }
            $result = array_merge($result, $logo);
            $logo = $result;
        }

        $asm = new waAppSettingsModel();
        $asm->set('webasyst', 'logo', json_encode($logo));
    }

    /**
     * @param waRequestFile $file
     * @throws waException
     * @return array [bool, string] - status and error text in case of status === false
     */
    public function setImage(waRequestFile $file)
    {
        // delete old image
        $this->deleteImage();

        $ext = $file->extension;

        $image = $file->waImage();

        if (function_exists('exif_read_data')) {
            $exif_data = @exif_read_data($file->tmp_name);
            if ($exif_data && !empty($exif_data['Orientation'])) {
                $this->correctOrientation($exif_data['Orientation'], $image);
            }
        }

        $filename = "logo.original.{$ext}";

        $image_path = wa()->getDataPath($filename, true, 'webasyst');

        // Save the original image in jpeg for future use
        $image->save($image_path);

        $name = substr($filename, 0, -strlen($ext)-1);

        $image = [
            'path' => $filename,
            'name' => $name,
            'ext' => $ext,
            'ts' => time()
        ];

        $this->setLogoSettings([
            'mode' => 'image',
            'image' => [
                'original' => $image
            ]
        ]);

        $sizes = ['64x64', '128x128', '192x192', '512x512'];

        $this->generateThumbs($image, $sizes, true);

        $thumbs = [];
        foreach ($sizes as $size) {
            $thumbs[$size]['path'] = $this->getThumbPath($image, $size, true);
            $thumbs[$size . '@2x']['path'] = $this->getThumbPath($image, $size . '@2x', true);
            $thumbs[$size]['ts'] = time();
            $thumbs[$size . '@2x']['ts'] = time();
        }

        $this->setLogoSettings([
            'image' => [
                'thumbs' => $thumbs
            ]
        ]);
    }

    public function deleteImage()
    {
        $logo = $this->get();

        if (empty($logo['image'])) {
            return;
        }

        if (!empty($logo['image']['thumbs'])) {
            foreach ($logo['image']['thumbs'] as $thumb) {
                $path = wa()->getDataPath($thumb['path'], true, 'webasyst');
                try {
                    waFiles::delete($path);
                } catch (Exception $exception) {

                }
            }
        }

        if (!empty($logo['image']['original'])) {
            $path = wa()->getDataPath($logo['image']['original']['path'], true, 'webasyst');
            try {
                waFiles::delete($path);
            } catch (Exception $exception) {

            }
        }

        unset($logo['image']);

        $logo['mode'] = 'gradient';
        $this->setLogoSettings($logo, false);
    }

    /**
     * Creates thumbnails of specified sizes for a logo image.
     *
     * @param array $image - original image
     *      - $image['path'] - relative file path
     *      - $image['name'] - name of image (part of file path without ext)
     *      - $image['ext'] - extension of image
     * @param array $sizes Array of image size values; e.g., '200x0', '96x96', etc.
     * @param bool $with_2x
     * @throws waException
     */
    public function generateThumbs($image, array $sizes, $with_2x = false)
    {
        foreach ($sizes as $size) {
            $this->generateThumbSize($image, $size, $with_2x);
        }
        clearstatcache();
    }

    /**
     * Returns path thumb
     *
     * @param array $image - original image
     *      - $image['name'] - name of image
     *      - $image['ext'] - extension of image
     * @param string $size Optional size value string (e.g., '200x0', '96x96', etc.).
     * @param bool $relative
     * @return string
     * @throws waException
     */
    private function getThumbPath($image, $size, $relative = false)
    {
        $filename = "{$image['name']}.{$size}.{$image['ext']}";
        if ($relative) {
            return $filename;
        }
        return wa()->getDataPath($filename, true, 'webasyst', false);
    }

    /**
     * @param array $image - original image
     *      - $image['path'] - relative file path
     *      - $image['name'] - name of image (part of file path without ext)
     *      - $image['ext'] - extension of image
     * @param $size
     * @param bool $with_2x
     * @throws waException
     */
    private function generateThumbSize($image, $size,$with_2x = false)
    {
        $save_thumb = function ($image_path, $thumb_path, $size, $is_2x) {
            if ($thumb_img = $this->generateThumb($image_path, $size)) {
                $quality = $is_2x ? 70 : 90;
                $thumb_img->save($thumb_path, $quality);
            }
        };

        $image_path = wa()->getDataPath($image['path'], true, 'webasyst');

        $thumb_path = $this->getThumbPath($image, $size);
        $save_thumb($image_path, $thumb_path, $size, false);

        if ($with_2x) {
            $thumb_path = $this->getThumbPath($image, $size.'@2x');

            $size = explode('x', $size);
            foreach ($size as &$s) {
                $s *= 2;
            }
            unset($s);
            $size = implode('x', $size);

            $save_thumb($image_path, $thumb_path, $size, true);
        }
    }

    /**
     * Returns image object for specified original image.
     *
     * @param string $src_image_path Path to original image
     * @param string $size Size value string of the form '200x0', '96x96', etc.
     * @param int|bool $max_size Optional maximum size limit
     * @return waImageImagick|waImageGd
     * @throws waException
     */
    private function generateThumb($src_image_path, $size, $max_size = false)
    {
        /** @var waImageImagick|waImageGd $image */
        $image = waImage::factory($src_image_path);

        $size_info = $this->parseSize($size);
        $type = $size_info['type'];
        $width = $size_info['width'];
        $height = $size_info['height'];

        switch ($type) {
            case 'max':
                if (is_numeric($max_size) && $width > $max_size) {
                    return null;
                }
                $image->resize($width, $height);
                break;
            case 'width':
            case 'height':
                if (is_numeric($max_size) && ($width > $max_size || $height > $max_size)) {
                    return null;
                }
                $image->resize($width, $height);
                break;
            case 'crop':
            case 'rectangle':
                if (is_numeric($max_size) && ($width > $max_size || $height > $max_size)) {
                    return null;
                }
                $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
                break;
            default:
                throw new waException("Unknown type");
                break;
        }

        return $image;
    }

    /**
     * Parses image size value string and returns size info array.
     *
     * @param string $size Size value string (e.g., '500x400', '500', '96x96', '200x0')
     * @returns array Size info array ('type', 'width', 'height')
     * @return array
     */
    private function parseSize($size)
    {
        $type = 'unknown';
        $ar_size = explode('x', $size);
        $width = !empty($ar_size[0]) ? $ar_size[0] : null;
        $height = !empty($ar_size[1]) ? $ar_size[1] : null;

        if (count($ar_size) == 1) {
            $type = 'max';
            $height = $width;
        } elseif ($width == $height) { // crop
            $type = 'crop';
        } elseif ($width && $height) { // rectangle
            $type = 'rectangle';
        } elseif (is_null($width)) {
            $type = 'height';
        } elseif (is_null($height)) {
            $type = 'width';
        }
        return array(
            'type'   => $type,
            'width'  => $width,
            'height' => $height
        );
    }

    private function correctOrientation($orientation, waImage $image)
    {
        $angles = array(
            3 => '180', 4 => '180',
            5 => '90',  6 => '90',
            7 => '-90', 8 => '-90'
        );
        if (isset($angles[$orientation])) {
            $image->rotate($angles[$orientation]);
            return true;
        }
        return false;
    }

    public static function isLogoFileName($filepath)
    {
        $info = pathinfo($filepath);
        return isset($info['basename']) && substr($info['basename'], 0, 5) === 'logo.';
    }

    /**
     * @return array of [
     *      'from' => <string:color in hex format>
     *      'to' =>  <string:color in hex format>
     *      'angle' => <int>
     * ]
     * @throws waException
     */
    public function getGradients()
    {
        if (!isset(self::$runtime_cache['logo'])) {
            self::$runtime_cache['logo'] = include wa('webasyst')->getConfig()->getConfigPath('logo.php', false, 'webasyst');
        }
        return self::$runtime_cache['logo']['gradients'];
    }
}
