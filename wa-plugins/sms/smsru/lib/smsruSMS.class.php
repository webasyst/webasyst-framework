<?php

class smsruSMS extends waSMSAdapter
{
    private $url = "https://sms.ru/sms/send";

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
        $post = array(
            "api_id" => $this->getOption('api_id'),
            "to"     => $to,
            "text"   => $text
        );
        // check from
        if ($from && preg_match('/^[a-z0-9_\.\-]+$/i', $from) && !preg_match('/^[0-9]+$/', $from)) {
            $post['from'] = $from;
        }

        $net = new waNet();
        try {
            $result = $net->query($this->url, $post, waNet::METHOD_POST);
            $this->log($to, $text, $result);
            $result = array_filter(preg_split('@[\r\n]+@', $result), 'strlen');

            $code = array_shift($result);
            $error = null;
            switch ($code) {
                case 100:
                    break;
                case 200:
                    $error = 'Неправильный api_id';
                    break;
                case 201:
                    $error = 'Не хватает средств на лицевом счету';
                    break;
                case 202:
                    $error = 'Неправильно указан получатель';
                    break;
                case 203:
                    $error = 'Нет текста сообщения';
                    break;
                case 204:
                    $error = 'Имя отправителя не согласовано с администрацией';
                    break;
                case 205:
                    $error = 'Сообщение слишком длинное (превышает 8 СМС)';
                    break;
                case 206:
                    $error = 'Будет превышен или уже превышен дневной лимит на отправку сообщений';
                    break;
                case 207:
                    $error = 'На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей';
                    break;
                case 208:
                    $error = 'Параметр time указан неправильно';
                    break;
                case 209:
                    $error = 'Вы добавили этот номер (или один из номеров) в стоп-лист';
                    break;
                case 210:
                    $error = 'Используется GET, где необходимо использовать POST';
                    break;
                case 211:
                    $error = 'Метод не найден';
                    break;
                case 212:
                    $error = 'Текст сообщения необходимо передать в кодировке UTF-8 (вы передали в другой кодировке)';
                    break;
                case 220:
                    $error = 'Сервис временно недоступен, попробуйте чуть позже.';
                    break;
                case 230:
                    $error = 'Превышен общий лимит количества сообщений на этот номер в день.';
                    break;
                case 231:
                    $error = 'Превышен лимит одинаковых сообщений на этот номер в минуту.';
                    break;
                case 232:
                    $error = 'Превышен лимит одинаковых сообщений на этот номер в день.';
                    break;
                case 300:
                    $error = 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)';
                    break;
                case 301:
                    $error = 'Неправильный пароль, либо пользователь не найден';
                    break;
                case 302:
                    $error = 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)';
                    break;
                default:
                    $error = sprintf('Неизвестный код ответа %s', $code);
            }

            if ($error) {
                $this->log($to, $text, sprintf('%d: %s', $code, $error));
                $result = false;
            }
        } catch (waException $ex) {
            $result = $ex->getMessage();
            $this->log($to, $text, $result);
            $result = false;
        }

        return $result;
    }
}
