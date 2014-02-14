<?php

class smsruSMS extends waSMSAdapter
{
    /**
     * @return array
     */
    public function getControls()
    {
        return array(
            'api_id' => array(
                'title'       => 'api_id',
                'description' => 'Введите значение параметра api_id для вашего аккаунта в сервисе sms.ru',
            ),
        );
    }

    /**
     * @param string $to
     * @param string $text
     * @param string $from
     * @return mixed
     */
    public function send($to, $text, $from = null)
    {
        // check CURL
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            $this->log($to, $text, "PHP extension curl required");
            return false;
        }
        $ch = curl_init("http://sms.ru/sms/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $post = array(
            "api_id" => $this->getOption('api_id'),
            "to"     => $to,
            "text"   => $text
        );
        // check from
        if ($from && preg_match("/^[a-z0-9_-]+$/i", $from) && !preg_match('/^[0-9]+$/', $from)) {
            $post['from'] = $from;
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $result = curl_exec($ch);
        curl_close($ch);

        $this->log($to, $text, $result);

        $result = explode("\n", $result);

        if ($result[0] == 100) {
            unset($result[0]);
            return $result;
        } else {
            return false;
        }
    }

}