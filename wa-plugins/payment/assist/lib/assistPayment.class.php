<?php
/**
 *
 * @property-read string $merchant
 * @property-read string $authorization
 * @property-read string $locale
 * @property-read int $sandbox
 * @property-read string $version
 * @property-read string $gate
 *
 */
class assistPayment extends waPayment
{
    private $url = array(
        'old'  => 'https://secure.assist.ru/shops/cardpayment.cfm',
        'new'  => 'https://payments%s.paysecure.ru/pay/order.cfm',
        'test' => 'https://test.paysecure.ru/pay/order.cfm',
    );
    public function allowedCurrency()
    {
        return true;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order_data = waOrder::factory($order_data);
        $view = wa()->getView();
        $view->assign('order', $order_data);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('order_id', $this->app_id.'_'.$order_data['order_id']);
        $view->assign('settings', $this->getSettings());
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');

    }

    private function getEndpointUrl()
    {
        if ($this->sandbox) {
            $url = $this->url['test'];
        } else {
            $url = sprintf($this->url[$this->version], $this->gate);
        }
        return $url;
    }

    public static function responceCodes()
    {
        $codes = array(
            'AS000' => 'АВТОРИЗАЦИЯ УСПЕШНО ЗАВЕРШЕНА',
            'AS100' => 'ОТКАЗ В АВТОРИЗАЦИИ',
            'AS101' => 'ОТКАЗ В АВТОРИЗАЦИИ. Ошибочный номер карты',
            'AS102' => 'ОТКАЗ В АВТОРИЗАЦИИ. Недостаточно средств',
            'AS104' => 'ОТКАЗ В АВТОРИЗАЦИИ. Неверный срок действия карты',
            'AS105' => 'ОТКАЗ В АВТОРИЗАЦИИ. Превышен лимит операций по карте',
            'AS106' => 'ОТКАЗ В АВТОРИЗАЦИИ. Неверный PIN',
            'AS107' => 'ОТКАЗ В АВТОРИЗАЦИИ. Ошибка приема данных',
            'AS108' => 'ОТКАЗ В АВТОРИЗАЦИИ. Подозрение на мошенничество',
            'AS109' => 'ОТКАЗ В АВТОРИЗАЦИИ. Превышен лимит операций ASSIST',
            'AS110' => 'ОТКАЗ В АВТОРИЗАЦИИ. Требуется авторизация по 3D-Secure',
            'AS200' => 'ПОВТОРИТЕ АВТОРИЗАЦИЮ',
            'AS300' => 'ОПЕРАЦИЯ ВЫПОЛНЯЕТСЯ. ЖДИТЕ',
            'AS400' => 'ПЛАТЕЖА С ТАКИМИ ПАРАМЕТРАМИ НЕ СУЩЕСТВУЕТ',
            'AS998' => 'ОШИБКА СИСТЕМЫ. Свяжитесь с ASSIST',
        );
        return $codes;
    }
}
