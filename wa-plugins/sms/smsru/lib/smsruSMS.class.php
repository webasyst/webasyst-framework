<?php

class smsruSMS extends waSMSAdapter
{
    public function getControls()
    {
        return array(
            'api_id' => array(
                'value' => '',
                'title' => 'api_id',
                'description' => 'Введите значение параметра api_id для вашего аккаунта в сервисе sms.ru',
            ),
        );
    }

    /**
     * @param string $to
     * @param string $text
     * @return mixed
     */
    public function send($to, $text)
    {
        $ch = curl_init("http://sms.ru/sms/send");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "api_id"    =>  $this->getOption('api_id'),
            "to"    =>  $to,
            "text"  =>  $text

        ));
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