<?php

class currencyquotesWidget extends waWidget
{
    protected $params;
    protected $currencies = array('EUR', 'USD');

    public function defaultAction()
    {
        $is_today = true;
        $nocache = $this->getRequest()->get('nocache');

        // looking maximum 10 days ago, if found first nonempty data make stop
        for ($days_ago = 1; $days_ago < 10; $days_ago += 1) {
            $today_date = date('Y-m-d', strtotime('-' . ($days_ago - 1) . ' day'));
            $today_quotes = $this->getQuotes($today_date, !$nocache);

            // try again and reset nocache flag (so now on take data from cache)
            if ($nocache && !$today_quotes) {
                $nocache = false;
                $today_quotes = $this->getQuotes($today_date, !$nocache);
            }

            if ($today_quotes) {

                // looking maximum 10 days ago
                for ($prev_shift = 0; $prev_shift < 9; $prev_shift += 1) {

                    $prev_date = date('Y-m-d', strtotime('-' . ($days_ago + $prev_shift) . ' day'));
                    $prev_quotes = $this->getQuotes($prev_date, !$nocache);

                    foreach ($today_quotes as &$quote) {
                        $prev_quotes[$quote['code']]['val'] = ifset($prev_quotes[$quote['code']]['val'], 0);
                        $diff = round($quote['val'] - $prev_quotes[$quote['code']]['val'], 4);
                        if ($diff == 0) {
                            continue 2;
                        }
                        $quote['diff'] = $diff;
                        $quote['diff_str'] = $quote['diff'] >= 0 ? '+' . $quote['diff'] : $quote['diff'];
                        $quote['val'] = round((float) $quote['val'], 4);
                    }
                    unset($quote);

                    break 2;

                }
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
    public function getQuotes($date, $from_cache = true)
    {
        $cache = $this->getCacheQuotes();
        if (!isset($cache[$date]) || !$from_cache) {

            $quotes = array();
            // make maximum 3 tries for loading quotes
            for ($try = 0, $pause = 1; $try < 3; $try += 1, $pause += 0.5) {
                $quotes = $this->loadQuotes(date('d/m/Y', strtotime($date)));
                if (!empty($quotes)) {
                    break;
                }
                sleep((int) $pause);
            }

            $cache[$date] = $quotes;
            $this->setCacheQuotes($cache);
        }
        return $cache[$date];
    }

    protected function loadQuotes($date)
    {
        $url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date;
        try {
            $response = $this->load($url);
            if (!$response) {
                return array();
            }
            $xml = new SimpleXMLElement($response);
            $quotes = array();
            foreach ($xml->xpath('Valute') as $quote) {
                $code = (string) $quote->CharCode;
                if (in_array($code, $this->currencies)) {
                    $quotes[$code] = array(
                        'code' => (string) $code,
                        'val' => str_replace(',', '.', (string) $quote->Value),
                    );
                }
            }
        } catch (Exception $e) {
            return array();
        }
        return $quotes;
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
        $params = $this->getParams();
        $quotes = array();
        if (isset($params['quotes'])) {
            $quotes = json_decode($params['quotes'], true);
        }
        $month_ago = strtotime('-1 month');
        $changed = false;
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
     * @param string $request
     * @return string
     * @throws waException
     */
    protected function load($url)
    {
        if (!extension_loaded('curl')) {
            throw new waException('Curl extension not loaded');
        }

        if (!function_exists('curl_init') || !($ch = curl_init())) {
            throw new waException("Can't init curl");
        }

        if (curl_errno($ch) != 0) {
            $error = "Can't init curl";
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch);
            throw new waException($error);
        }
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER,  1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  0);
        @curl_setopt($ch, CURLOPT_TIMEOUT,        15);
        @curl_setopt($ch, CURLOPT_HEADER, 0);
        @curl_setopt($ch, CURLOPT_URL, $url);

        $result = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $error = "Curl executing error";
            $error .= ": ".curl_errno($ch)." - ".curl_error($ch).". Url: {$url}";
            throw new waException($error);
        }

        curl_close($ch);

        return $result;
    }

}