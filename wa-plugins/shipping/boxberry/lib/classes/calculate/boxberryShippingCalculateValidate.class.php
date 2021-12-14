<?php

/**
 * Class boxberryShippingCalculateValidate
 */
class boxberryShippingCalculateValidate
{
    /**
     * @var boxberryShipping
     */
    protected $bxb = null;

    /**
     * boxberryShippingCalculateValidate constructor.
     * @param boxberryShipping $bxb
     */
    public function __construct(boxberryShipping $bxb)
    {
        $this->bxb = $bxb;
    }

    /**
     * @return bool
     */
    public function getErrors()
    {
        $errors = false;

        if (!$this->bxb->api_url) {
            $errors = true;
        }

        if (!$this->bxb->token) {
            $errors = true;
        }

        if (!$this->bxb->targetstart) {
            $errors = true;
        }

        if (!$errors && $this->validateRegions()) {
            $errors = true;
        }

        // Weight not specified
        if ($errors && $this->bxb->getParcelWeight() == 0) {
            $errors = true;
        }

        // Parcel weight exceeds maximum
        if (!$errors && ($this->bxb->getParcelWeight() > $this->bxb->max_weight)) {
            $errors = true;
        }

        if (!$errors) {
            $errors = $this->validateDimensions();
        }


        return $errors;
    }

    /**
     * Checks if delivery to the specified address is available.
     * @return bool
     */
    public function validateRegions()
    {
        $country = $this->bxb->getAddress('country');
        $region = $this->bxb->getAddress('region');
        $city = trim(mb_strtolower($this->bxb->getAddress('city')));

        $settings_countries = $this->bxb->getSettings('countries');
        $settings_regions = $this->bxb->getSettings('regions');
        $settings_cities = $this->bxb->cities;

        if (empty($settings_countries)) {
            $settings_countries = array_flip(boxberryShippingCountriesAdapter::getAllowedCountries());
        }
        $error = array_search($country, $settings_countries) === false;

        //If the region is enabled in the settings, then we check the region from the checkout
        if (!$error && !empty($settings_regions[$country])
            && array_search($region, $settings_regions[$country]) === false
        ) {
            $error = true;
        }

        // If cities are included in the settings, you need to check that the city from the checkout is in the list
        if (!$error && $settings_cities) {
            $settings_city_list = explode(',', $settings_cities);

            $found = boxberryShippingCalculateHelper::findCityName($city, $settings_city_list);
            if (!$found) {
                $error = true;
            }
        }

        return $error;
    }

    /**
     * @return bool
     */
    public function validateDimensions()
    {
        $plugin_sizes = $this->bxb->getTotalSize();
        $errors = false;
        // If the plugin has not returned the dimensions, then everything is fine.
        // You cannot save standard sizes larger than maximum in plugin settings
        if (!$plugin_sizes) {
            return $errors;
        }

        //paranoid mode on
        $filtered_plugin_sizes = [
            'width'  => $plugin_sizes['width'],
            'height' => $plugin_sizes['height'],
            'length' => $plugin_sizes['length'],
        ];

        $max_default_sizes = [
            'width'  => $this->bxb->max_width,
            'height' => $this->bxb->max_height,
            'length' => $this->bxb->max_length,
        ];

        // Compare the maximum sizes from the two arrays.
        // We cannot compare by key from an array, because the plugin and the user can understand the dimensions differently.
        while ($max_default_sizes) {
            // We take the maximum of sizes
            $max_filtered = max($filtered_plugin_sizes);
            $max_default = max($max_default_sizes);

            // If the size of either side is exceeded, then the package is not valid
            if ($max_filtered > $max_default) {
                $errors = true;
                break;
            } else {
                // Sizes from the plugin not exceeding the maximum - valid
                // We extract the keys of the maximum values
                $filtered_key = array_search($max_filtered, $filtered_plugin_sizes);
                $default_key = array_search($max_filtered, $filtered_plugin_sizes);

                // Delete checked values ​​to compare the following max values
                unset($filtered_plugin_sizes[$filtered_key]);
                unset($max_default_sizes[$default_key]);
            }
        }

        return $errors;
    }
}
