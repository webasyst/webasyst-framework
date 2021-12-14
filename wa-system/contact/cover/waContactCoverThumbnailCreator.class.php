<?php

class waContactCoverThumbnailCreator
{
    protected $options = [];

    /**
     * @param array $options
     *      int $options['sharpen_amount']
     *          Default is 6, if pass 0 then without sharpen applied
     *      int $options['save_quality']
     *          Default is 90. Must be integer from 0 to 100
     *      bool $options['correct_orientation']
     *          Default is TRUE
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'sharpen_amount' => 6,
            'save_quality' => 90,
            'correct_orientation' => true,
        ], $options);

        $this->options['sharpen_amount'] = wa_is_int($this->options['sharpen_amount']) ?
                                                intval($this->options['sharpen_amount']) : 0;

        $this->options['save_quality'] = wa_is_int($this->options['save_quality']) ?
                                                intval($this->options['save_quality']) : 0;
        $this->options['save_quality'] = min($this->options['save_quality'], 100);
        $this->options['save_quality'] = max($this->options['save_quality'], 0);
    }

    /**
     * @param $original_photo_path
     * @param $path
     * @param $size
     * @return bool
     * @throws waException
     */
    public function create($original_photo_path, $path, $size)
    {
        $size_info = $this->parseSize($size);
        $image = $this->newThumbnailImage($original_photo_path,
            $size_info['type'],
            $size_info['width2x'],
            $size_info['height2x'],
            $this->options['sharpen_amount'],
            $this->options['correct_orientation']
        );
        if ($image && $this->options['save_quality'] > 0) {
            $image->save($path, $this->options['save_quality']);
            return true;
        }
        return false;
    }


    /**
     * Parsing size-code (e.g. 500x400, 500, 96x96, 200x0) into key-value array with info about this size
     *
     * @param string $size
     * @returns array
     * @return array
     */
    protected function parseSize($size)
    {
        $ar_size = explode('x', $size);

        if (count($ar_size) < 1 || !wa_is_int($ar_size[0])) {
            return [
                'type' => 'unknown',
                'width' => null,
                'height' => null,
                'width2x' => null,
                'height2x' => null
            ];
        }

        $width = !empty($ar_size[0]) ? intval($ar_size[0]) : null;
        $height = !empty($ar_size[1]) ? intval($ar_size[1]) : null;

        $type = 'unknown';

        if (count($ar_size) == 1) {
            $type = 'max';
            $height = $width;
        } else {
            if ($width == $height) {  // crop
                $type = 'crop';
            } else {
                if ($width && $height) { // rectangle
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
            'height' => $height,
            'width2x' => $width !== null ? 2 * $width : null,
            'height2x' => $height !== null ? 2 * $height : null,
        );
    }

    /**
     * @param $path
     * @param $type
     * @param $width
     * @param $height
     * @param $sharpen_amount
     * @param $correct_orientation
     * @return waImage|null
     */
    protected function newThumbnailImage($path, $type, $width, $height, $sharpen_amount, $correct_orientation)
    {
        if (!in_array($type, ['crop', 'rectangle', 'max', 'width', 'height'])) {
            return null;
        }

        $exif_data = [];
        if (!empty($correct_orientation) && function_exists('exif_read_data')) {
            $exif_data = @exif_read_data($path);
        }

        $image = $this->newImage($path);
        $image = $this->prepareImage($image, [
            'exif' => $exif_data
        ]);

        switch($type) {
            case 'crop':
                $image->resize($width, $height, waImage::INVERSE)->crop($width, $height);
                break;
            case 'rectangle':
                // 4 combinations

                // width x height - current proportion to adjust photo
                // crop_width x crop_height - when we first need to crop content of current photo,
                // because we can't harmonically adjust to new proportion

                // case of album orientation of thumbnail
                if ($width > $height) {
                    $crop_width  = $image->width;
                    $crop_height = $image->width*$height/$width;
                    if ($crop_height > $image->height) {
                        $crop_height = $image->height;
                        $crop_width  = $image->height*$width/$height;
                    }
                } else {    // case of vertical orientation of thumbnail
                    $crop_height = $image->height;
                    $crop_width = $image->height*$width/$height;
                    if ($crop_width > $image->width) {
                        $crop_width = $image->width;
                        $crop_height = $image->width*$height/$width;
                    }
                }
                $image->crop($crop_width, $crop_height)->resize($width, $height, waImage::INVERSE);
                break;
            case 'max':
            case 'width':
            case 'height':
                $image->resize($width, $height);
                break;
            default:
                break;
        }

        if ($sharpen_amount > 0) {
            $image->sharpen($sharpen_amount);
        }

        return $image;
    }

    protected function newImage($path)
    {
        return waImage::factory($path);
    }

    protected function prepareImage(waImage $image, array $params)
    {
        if (!empty($params['exif']['Orientation'])) {
            $this->correctOrientation($params['exif']['Orientation'], $image);
        }
        return $image;
    }

    protected function correctOrientation($orientation, waImage $image)
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

}
