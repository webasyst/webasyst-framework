<?php

/**
 * waImage for photos
 *
 * @see waImage
 *
 * @method photosImage rotate(int $degrees)
 * @method photosImage resize(int $width = null, int $height = null, string $master = null, bool $deny_exceed_original_sizes = true)
 * @method photosImage crop(int $width, int $height, $offset_x = waImage::CENTER, $offset_y = waImage::CENTER, $deny_exceed_original_sizes = true)
 * @method photosImage sharpen(int $amount)
 * @method photosImage filter(string $type)
 * @method photosImage watermark(array $options)
 * @method photosImage getExt
 *
 * @property $width
 * @property $height
 * @property $type
 * @property $ext
 */
class photosImage
{
    public $file;
    /**
     * @var waImage
     */
    protected $image;

    public function __construct($file)
    {
        $this->file = $file;
        $this->image = waImage::factory($file);
    }

    public function __destruct()
    {
        $this->image->__destruct();
    }

    public function save($file = null, $quality = null)
    {
        $config = wa('photos')->getConfig();
        if($quality === null) {
            $quality = $config->getSaveQuality();
        }
        // check save_original option
        if ($config->getOption('save_original')) {
            // get original file name
            $original_file = photosPhoto::getOriginalPhotoPath($this->file);
            // save original file if it not exists
            if (!file_exists($original_file)) {
                copy($this->file, $original_file);
            }
        }
        // save image
        return $this->image->save($file, $quality);
    }

    public function __get($name)
    {
        return $this->image->$name;
    }

    /**
     *
     * @param $method
     * @param $arguments
     * @return photosImage
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->image, $method)) {
            call_user_func_array(array($this->image, $method), $arguments);
        }
        return $this;
    }
}


