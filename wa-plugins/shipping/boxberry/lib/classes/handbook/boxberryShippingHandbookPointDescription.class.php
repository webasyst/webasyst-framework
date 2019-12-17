<?php

/**
 * Class boxberryShippingHandbookPointDescription
 */
class boxberryShippingHandbookPointDescription extends boxberryShippingHandbookManager
{
    /**
     * Size to which images will be converted
     */
    const POINT_IMAGE_SIZE = '970';

    /**
     * @var string Point Code
     */
    protected $code = '';

    /**
     * boxberryShippingHandbookPointDescription constructor.
     * @param boxberryShippingApiManager $api_manager
     * @param $data
     * @throws waException
     */
    public function __construct(boxberryShippingApiManager $api_manager, $data)
    {
        $this->validateData($data);

        parent::__construct($api_manager, $data);
        $this->code = ifset($data, 'code', '');
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'descriptions/point'.$this->code;
    }

    /**
     * @return array
     */
    protected function getFromAPI()
    {
        $raw_description = $this->api_manager->downloadPointDescription(['code' => $this->code, 'photo' => 1, boxberryShippingApiManager::LOG_PATH_KEY => $this->getCacheKey()]);
        $description = [];

        if (!empty($raw_description)) {
            $description = $this->parseDescription($raw_description);

            $cache = [
                'key'   => $this->getCacheKey(),
                'ttl'   => 604800, // 1 week
                'value' => $description,
            ];

            $this->setToCache($cache);
        }

        return $description;
    }

    /**
     * Searches for required keys
     * @param $data
     * @throws waException
     */
    protected function validateData($data)
    {
        $code = ifset($data, 'code', '');
        $id = ifset($data, 'id', '');
        if (!$code || !$id) {
            throw new waException('Plugin ID and point code required');
        }
    }

    /**
     * @param array $raw_description
     * @return array
     */
    public function parseDescription(array $raw_description)
    {
        $raw_photos = ifset($raw_description, 'photos', []);

        $result = [
            'code'           => ifset($raw_description, 'extraCode', ''),
            'acquiring'      => ifset($raw_description, 'Acquiring', ''),
            'fitting'        => ifset($raw_description, 'EnableFitting', ''),
            'short_schedule' => ifset($raw_description, 'WorkShedule', ''),
            'schedule'       => $this->parseSchedule($raw_description),
            'photos'         => [],
        ];

        if ($raw_photos) {
            foreach ($raw_photos as $index => $base64_not_encoded) {
                $result['photos'][]['uri'] = $this->saveImage($base64_not_encoded);
            }
        }

        return $result;
    }

    /**
     * Parsing the work schedule of the issuing point
     *
     * @param array $raw_description
     * @return array
     */
    public function parseSchedule(array $raw_description)
    {
        // Map of the days of boxberry and webasyst
        $day_map = [
            '1' => 'Mo',
            '2' => 'Tu',
            '3' => 'We',
            '4' => 'Th',
            '5' => 'Fr',
            '6' => 'Sa',
            '0' => 'Su',
        ];

        $result = [];

        foreach ($day_map as $wa_day => $bxb_day) {
            // The name of the keys comes from boxberry
            $begin = 'Work'.$bxb_day.'Begin';
            $end = 'Work'.$bxb_day.'End';
            $start_work = ifset($raw_description, $begin, null);

            $type = 'weekend';
            if ($start_work) {
                $type = 'workday';
            }

            $result[$wa_day] = [
                'type'       => $type,
                'start_work' => $start_work,
                'end_work'   => ifset($raw_description, $end, null)
            ];
        }

        return $result;
    }

    /**
     * Saves images for point of issue
     *
     * @param $base64 image in base 64 format
     * @return bool|string
     */
    protected function saveImage($base64)
    {
        $filename = md5($base64).'.jpg';
        $path = $this->getImagePath($filename);
        $url = $this->getImageUrl($filename);

        /**
         * If the image has already been saved, no need to re-save it. The base64 hash ensures that these are the same pictures.
         */
        if (file_exists($path)) {
            return $url;
        }

        if (waFiles::create($path)) {
            $encoded_base64 = base64_decode($base64);
            // Save base64 as jpg
            $ifp = fopen($path, 'wb');
            fwrite($ifp, $encoded_base64);
            fclose($ifp);

            // Trying to resize
            try {
                $image = waImage::factory($path);
                $image->resize(self::POINT_IMAGE_SIZE);
                $image->save();
            } catch (waException $e) {

            }
        } else {
            $url = false;
        }

        return $url;
    }

    /**
     * @param $filename
     * @return string
     */
    protected function getImageUrl($filename)
    {
        $sub_path = $this->getCacheKey();
        $filepath = $sub_path.'/'.$filename;

        $url = wa()->getDataUrl($filepath, true, $this->getAppSubPath());

        return $url;
    }

    /**
     * @param $filename
     * @return string
     */
    protected function getImagePath($filename)
    {
        $sub_path = $this->getCacheKey();
        $filepath = $sub_path.'/'.$filename;

        return wa()->getDataPath($filepath, true, $this->getAppSubPath(), true);
    }

    /**
     * @return string
     */
    protected function getAppSubPath()
    {
        return 'webasyst/shipping/'.$this->data['id'];
    }
}
