<?php

class weatherWidget extends waWidget
{
    protected $params;

    public function defaultAction()
    {
        $city = $this->getSettings('city');
        $unit = $this->getUnit();
        $nocache = $this->getRequest()->get('nocache');

        if (!$city) {
            $user = wa()->getUser();
            if (!$user->getId() && $this->info['dashboard_id']) {
                $user = new waContact($this->info['contact_id']);
            }
            try {
                $addresses = $user->get('address:city');
                foreach ($addresses as $address) {
                    if (!empty($address['value'])) {
                        $city = $address['value'];
                        break;
                    }
                }
            } catch (waException $e) {
            }
        }

        $weather = null;
        if ($city) {
            // Fetch up-to-date data if asked to bypass cache.
            if ($nocache) {
                $weather = $this->getWeather(date('Y-m-d H'), $city, $unit, false);
            }

            // Get from cache.
            if (!$weather) {
                $weather = $this->getWeatherFromCache($city, $unit);
            }

            // Fetch up-to-date data if there's nothing in cache and we didn't try already.
            if (!$weather && !$nocache) {
                $weather = $this->getWeather(date('Y-m-d H'), $city, $unit, false);
            }
        }

        $this->display(array(
            'info' => $this->getInfo(),
            'city' => $city,
            'weather' => $weather,
            'unit' => $unit
        ));
    }

    /** Returns most recent weather from cache. Looks for up to 30 hours back. */
    public function getWeatherFromCache($city, $unit)
    {
        for ($hours_ago = 0; $hours_ago < 30; $hours_ago += 1) {
            $date_with_hour = date('Y-m-d H', strtotime('-'.$hours_ago.' hour'));
            $weather = $this->getWeather($date_with_hour, $city, $unit, true);
            if ($weather) {
                return $weather;
            }
        }
        return array();
    }

    /**
     * @param string $date_with_hour datetime in 'Y-m-d H' format
     * @param string $city
     * @param string $unit C or F
     * @return array|null
     */
    public function getWeather($date_with_hour, $city, $unit = 'C', $from_cache = true)
    {
        $cache = $this->getCacheWeather();
        if (!$from_cache && (empty($cache[$date_with_hour][$city]) || !empty($cache[$date_with_hour][$city]['message']))) {

            $weather = array();

            // Close the session to allow parallel HTTP requests during this (potentially long) operation
            wa()->getStorage()->close();

            // make maximum 3 tries for loading weather
            for ($tries = 3; $tries > 0; $tries--) {
                try {
                    $weather = $this->loadWeather($city);
                    if ($weather) {
                        $cache[$date_with_hour][$city] = $weather;
                        $this->setCacheWeather($cache);
                        break;
                    }
                } catch (Exception $e) {
                    // Something's badly wrong (as opposed to an empty result)
                    // and we should not try again.
                    $weather = array(
                        'message' => $e->getMessage(),
                    );
                    break;
                }
                $tries > 1 && sleep(4 - $tries);
            }

            // Open the session storage again
            wa()->getStorage()->open();
        }

        $weather = ifset($cache[$date_with_hour][$city], array());

        if ($unit === 'C') {
            foreach (array('temp', 'temp_min', 'temp_max') as $temp_key) {
                if (isset($weather['main'][$temp_key])) {
                    $temp_F = $weather['main'][$temp_key];
                    $weather['main'][$temp_key] = ($temp_F - 32) * 5 / 9;
                }
            }
        }

        foreach (array('temp', 'temp_min', 'temp_max') as $temp_key) {
            if (isset($weather['main'][$temp_key])) {
                $weather['main'][$temp_key] = (int)round($weather['main'][$temp_key], 1);
            }
        }

        if (!empty($weather['weather'])) {
            if (isset($weather['weather'][0])) {
                $weather['weather'] = $weather['weather'][0];
            }
        }

        return $weather;
    }

    /**
     *
     * @param string $city
     * @return array
     */
    protected function loadWeather($city)
    {
        $url = 'http://api.openweathermap.org/data/2.5/weather?q='.urlencode($city).'&units=imperial&APPID=e4316f7f92cf085f40ee95a98908e8d6';
        $response = $this->load($url);
        if (!$response) {
            return array();
        }
        return json_decode($response, true);
    }

    protected function getParams()
    {
        if ($this->params === null) {
            $model = new waWidgetModel();
            $info = $this->getInfo();
            $params = $model->getParams($info['id']);
            $this->params = $params;
        }
        return $this->params;
    }

    protected function setParams($params)
    {
        $model = new waWidgetModel();
        $info = $this->getInfo();
        $model->setParams($info['id'], $params);
        $this->params = $params;
    }

    public function getUnit()
    {
        $unit = $this->getSettings('unit');
        return $unit ? $unit : 'C';
    }

    public function getCacheWeather()
    {
        $params = $this->getParams();

        $weathers = array();
        if (isset($params['weathers'])) {
            $weathers = json_decode($params['weathers'], true);
        }
        $week_ago = strtotime('-1 week');
        $changed = false;
        foreach ($weathers as $dt_with_hour => $w) {
            $dt_with_hour .= ':00:00';
            if (strtotime($dt_with_hour) < $week_ago) {
                $changed = true;
                unset($weathers[$dt_with_hour]);
            }
        }
        if ($changed) {
            $params['weathers'] = json_encode($weathers);
            $this->setParams($params);
        }
        return $weathers;
    }

    public function setCacheWeather($cache)
    {
        $params = $this->getParams();
        $params['weathers'] = json_encode($cache);
        $this->setParams($params);
    }

    /**
     * @param string $url
     * @return string
     * @throws waException
     */
    protected function load($url)
    {
        if (!extension_loaded('curl')) {
            if (ini_get('allow_url_fopen')) {
                $default_socket_timeout = @ini_set('default_socket_timeout', 10);
                $result = file_get_contents($url);
                @ini_set('default_socket_timeout', $default_socket_timeout);
            } else {
                throw new waException('Curl extension not loaded');
            }
        } else {

            if (!function_exists('curl_init') || !($ch = curl_init())) {
                throw new waException("Can't init curl");
            }

            if (curl_errno($ch) != 0) {
                $error = "Can't init curl";
                $error .= ": ".curl_errno($ch)." - ".curl_error($ch);
                throw new waException($error);
            }
            @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            @curl_setopt($ch, CURLOPT_HEADER, 0);
            @curl_setopt($ch, CURLOPT_URL, $url);

            $result = @curl_exec($ch);
            if (curl_errno($ch) != 0) {
                //$error = "Curl executing error";
                //$error .= ": ".curl_errno($ch)." - ".curl_error($ch).". Url: {$url}";
                //throw new waException($error);

                // This is kinda non-fatal, so we don't throw an exception here
                $result = '';
            }

            curl_close($ch);
        }

        return $result;
    }

    public static function customFieldLabel($field_name, $field_params)
    {
        $uniqid = "s".uniqid(true);
        return $field_params['value'].'<script id="'.$uniqid.'">$(function() {
            $("#'.$uniqid.'").closest(".value").addClass("no-shift");
        });</script>';
    }
}

