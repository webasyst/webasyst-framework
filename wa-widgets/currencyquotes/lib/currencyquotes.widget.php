<?php

class currencyquotesWidget extends waWidget
{
    protected $params;
    protected $currencies = array('EUR', 'USD');

    public function defaultAction()
    {
        $is_today = true;
        $allow_api = true;
        $nocache = $this->getRequest()->get('nocache');
        wa()->getStorage()->close();

        // Start from tomorrow and look back up to 10 days ago, fetching quotes via cache or API
        for ($days_ago = 0; $days_ago < 10; $days_ago += 1) {
            $today_date = date('Y-m-d', strtotime((1-$days_ago) . ' day'));

            // Try to get data from API or cache.
            // First request is always via API. Other requests prefer cache.
            $today_quotes = $this->getQuotes($today_date, ($is_today || $nocache) ? 'force' : $allow_api);

            // When fetching data failed it means something's wrong with API.
            // We reset flags, from now on taking data from cache only.
            if ($nocache && !$today_quotes) {
                $allow_api = $nocache = false;
                $today_quotes = $this->getQuotes($today_date, false);
            }

            if ($today_quotes) {

                // Defaults in case we can't fetch previous quote to compare to
                foreach ($today_quotes as &$quote) {
                    $quote['val'] = round((float) $quote['val'], 4);
                    $quote['diff_str'] = null;
                    $quote['diff'] = null;
                }
                unset($quote);

                // looking maximum 10 days ago to find previous quote
                for ($prev_shift = 0; $prev_shift < 9; $prev_shift += 1) {

                    $prev_date = date('Y-m-d', strtotime('-' . ($days_ago + $prev_shift) . ' day'));
                    $prev_quotes = $this->getQuotes($prev_date, $nocache || $allow_api);

                    foreach ($today_quotes as &$quote) {
                        $prev_quotes[$quote['code']]['val'] = ifset($prev_quotes[$quote['code']]['val'], 0);
                        $diff = round($quote['val'] - $prev_quotes[$quote['code']]['val'], 4);
                        if ($diff == 0) {
                            continue 2;
                        }
                        $quote['diff'] = $diff;
                        $quote['diff_str'] = $quote['diff'] >= 0 ? '+' . $quote['diff'] : $quote['diff'];
                    }
                    unset($quote);
                    break;
                }

                break;
            }
            $is_today = false;
        }

        $this->display(array(
            'quotes' => $today_quotes,
            'is_today' => $is_today,
            'date' => $today_date,
            'info' => $this->getInfo()
        ));
    }

    /**
     *
     * @param Y-m-d datetime $date
     * @return array
     */
    public function getQuotes($date, $allow_api = true)
    {
        $cache = $this->getCacheQuotes();
        if ($allow_api === 'force' || ($allow_api && empty($cache[$date]))) {
            // make maximum 3 tries for loading quotes
            for ($try = 0, $pause = 1; $try < 3; $try += 1, $pause += 0.5) {
                $quotes = $this->loadQuotes(date('d/m/Y', strtotime($date)));
                if (!empty($quotes)) {
                    $cache[$date] = $quotes;
                    $this->setCacheQuotes($cache);
                    break;
                }
                sleep((int) $pause);
            }
        }
        return ifempty($cache[$date], array());
    }

    protected function loadQuotes($date)
    {
        $url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date;
        try {
            $response = $this->load($url);
            if ($response) {
                $quotes = array();
                $xml = new SimpleXMLElement($response);
                foreach ($xml->xpath('Valute') as $quote) {
                    $code = (string) $quote->CharCode;
                    if (in_array($code, $this->currencies)) {
                        $quotes[$code] = array(
                            'code' => (string) $code,
                            'val' => str_replace(',', '.', (string) $quote->Value),
                        );
                    }
                }
                return $quotes;
            }
        } catch (Exception $e) {
            waLog::log('Unable to get data from currencyquotes widget API: '.$e->getMessage());
        }
        return array();
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

    public function getCacheQuotes()
    {
        // Load cache
        $params = $this->getParams();
        $quotes = array();
        if (!empty($params['quotes'])) {
            $quotes = @json_decode($params['quotes'], true);
            if (!$quotes) {
                $quotes = array();
            }
        }

        // Remove items older than a month
        $changed = false;
        $month_ago = strtotime('-1 month');
        foreach ($quotes as $dt => $q) {
            if (strtotime($dt) < $month_ago) {
                $changed = true;
                unset($quotes[$dt]);
            }
        }
        if ($changed) {
            $params['quotes'] = json_encode($quotes);
            $this->setParams($params);
        }

        return $quotes;
    }

    public function setCacheQuotes($cache)
    {
        $params = $this->getParams();
        $params['quotes'] = json_encode($cache);
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
            @curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            @curl_setopt($ch, CURLOPT_HEADER, 0);
            @curl_setopt($ch, CURLOPT_URL, $url);

            $result = @curl_exec($ch);
            if (curl_errno($ch) != 0) {
                $error = "Curl executing error";
                $error .= ": ".curl_errno($ch)." - ".curl_error($ch).". Url: {$url}";
                throw new waException($error);
            }

            curl_close($ch);
        }

        return $result;
    }

}
