<?php

/**
 *
 * @author Webasyst
 * @name WebMoney
 * @description Плагин оплаты через WebMoney.
 *
 * Поля, доступные в виде параметров настроек плагина, указаны в файле lib/config/settings.php.
 * @property-read string $LMI_MERCHANT_ID
 * @property-read string $LMI_PAYEE_PURSE
 * @property-read string $secret_key
 * @property-read string $LMI_SIM_MODE
 * @property-read string $TESTMODE
 * @property-read string $protocol
 * @property-read string $hash_method
 */
class webmoneyPayment extends waPayment implements waIPayment
{

    const PROTOCOL_WEBMONEY = 'webmoney';
    const PROTOCOL_WEBMONEY_LEGACY = 'webmoney_legacy';
    const PROTOCOL_PAYMASTER = 'paymaster';
    const PROTOCOL_WEBMONEY_LEGACY_COM = 'webmoney_legacy_com';
    const PROTOCOL_PAYMASTER_COM = 'paymaster_com';

    /**
     * Возвращает ISO3-коды валют, поддерживаемых платежной системой,
     * допустимые для выбранного в настройках протокола подключения и указанного номера кошелька продавца.
     *
     * @see waPayment::allowedCurrency()
     * @return mixed
     */
    public function allowedCurrency()
    {
        $currency = false;

        /**
         * В зависимости от выбранного в настройках протокола подключения возвращаем либо массив всех поддерживаемых валют,
         * либо код валюты, соответствующей кошельку продавца, указанному в настройках.
         * Если во втором случае поддерживаемая валюта не определена, возвращаем false.
         */
        switch ($this->protocol) {
            case self::PROTOCOL_WEBMONEY_LEGACY:
            case self::PROTOCOL_PAYMASTER:
            case self::PROTOCOL_WEBMONEY_LEGACY_COM:
            case self::PROTOCOL_PAYMASTER_COM:
                $currency = array('RUB', 'UAH', 'USD', 'EUR', 'UZS', 'BYR');
                break;
            case self::PROTOCOL_WEBMONEY:
            default:
                $currency_map = array(
                    'R' => 'RUB',
                    'U' => 'UAH',
                    'Z' => 'USD',
                    'E' => 'EUR',
                    'D' => 'USD',
                    'Y' => 'UZS',
                    'B' => 'BYR',
                );
                $pattern = '/^(['.implode('', array_keys($currency_map)).'])\d+$/i';
                if (preg_match($pattern, trim($this->LMI_PAYEE_PURSE), $matches)) {
                    $key = strtoupper($matches[1]);
                    if (isset($currency_map[$key])) {
                        $currency = $currency_map[$key];
                    }
                }
                break;
        }
        return $currency;
    }

    /**
     * Генерирует HTML-код формы оплаты.
     *
     * Платежная форма может отображаться во время оформления заказа или на странице просмотра ранее оформленного заказа.
     * Значение атрибута "action" формы может содержать URL сервера платежной системы либо URL текущей страницы (т. е. быть пустым).
     * Во втором случае отправленные пользователем платежные данные снова передаются в этот же метод для дальнейшей обработки, если это необходимо,
     * например, для проверки, сохранения в базу данных, перенаправления на сайт платежной системы и т. д.
     * @param array $payment_form_data Содержимое POST-запроса, полученное при отправке платежной формы
     *     (если в формы оплаты не указано значение атрибута "action")
     * @param waOrder $order_data Объект, содержащий всю доступную информацию о заказе
     * @param bool $auto_submit Флаг, обозначающий, должна ли платежная форма автоматически отправить данные без участия пользователя
     *     (удобно при оформлении заказа)
     * @return string HTML-код платежной формы
     * @throws waException
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        // заполняем обязательный элемент данных с описанием заказа
        if (empty($order_data['description'])) {
            $order_data['description'] = 'Заказ '.$order_data['order_id'];
        }

        // вызываем класс-обертку, чтобы гарантировать использование данных в правильном формате
        $order = waOrder::factory($order_data);

        // добавляем в платежную форму поля, требуемые платежной системой WebMoney
        $hidden_fields = array(
            'LMI_MERCHANT_ID'        => $this->LMI_MERCHANT_ID,
            'LMI_PAYMENT_AMOUNT'     => number_format($order->total, 2, '.', ''),
            'LMI_CURRENCY'           => strtoupper($order->currency),
            'LMI_PAYMENT_NO'         => $order_data['order_id'],
            'LMI_PAYMENT_DESC'       => $order->description,
            'LMI_RESULT_URL'         => $this->getRelayUrl(),
            'wa_app'                 => $this->app_id,
            'wa_merchant_contact_id' => $this->merchant_id,
        );
        if ($this->LMI_PAYEE_PURSE) {
            $hidden_fields['LMI_PAYEE_PURSE'] = $this->LMI_PAYEE_PURSE;
        }
        if ($this->TESTMODE) {
            $hidden_fields['LMI_SIM_MODE'] = $this->LMI_SIM_MODE;
        }
        if (!empty($order_data['customer_info']['email'])) {
            $hidden_fields['LMI_PAYER_EMAIL'] = $order_data['customer_info']['email'];
        }

        $transaction_data = $this->formalizeData($hidden_fields);

        // добавляем служебные URL:

        // URL возврата покупателя после успешного завершения оплаты
        $hidden_fields['LMI_SUCCESS_URL'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);

        // URL возврата покупателя после неудачной оплаты
        $hidden_fields['LMI_FAILURE_URL'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

        switch ($this->protocol) {
            case self::PROTOCOL_PAYMASTER:
            case self::PROTOCOL_WEBMONEY_LEGACY:
            case self::PROTOCOL_PAYMASTER_COM:
            case self::PROTOCOL_WEBMONEY_LEGACY_COM:
                break;
            case self::PROTOCOL_WEBMONEY:
            default:
                unset($hidden_fields['LMI_CURRENCY']);
                if (strpos(waRequest::getUserAgent(), 'MSIE') !== false) {
                    $hidden_fields['LMI_PAYMENT_DESC'] = $order->description_en;
                }
                break;
        }

        $view = wa()->getView();

        $view->assign('url', wa()->getRootUrl());
        $view->assign('hidden_fields', $hidden_fields);

        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('form_options', $this->getFormOptions());
        $view->assign('auto_submit', $auto_submit);

        // для отображения платежной формы используем собственный шаблон
        return $view->fetch($this->path.'/templates/payment.html');
    }

    /**
     * Инициализация плагина для обработки вызовов от платежной системы.
     *
     * Для обработки вызовов по URL вида /payments.php/webmoney/* необходимо определить
     * соответствующее приложение и идентификатор, чтобы правильно инициализировать настройки плагина.
     * @param array $request Данные запроса (массив $_REQUEST)
     * @return waPayment
     * @throws waPaymentException
     */
    protected function callbackInit($request)
    {
        if (!empty($request['LMI_PAYMENT_NO']) && !empty($request['wa_app']) && !empty($request['wa_merchant_contact_id'])) {
            $this->app_id = $request['wa_app'];
            $this->merchant_id = $request['wa_merchant_contact_id'];
        } else {
            self::log($this->id, array('error' => 'empty required field(s)'));
            throw new waPaymentException('Empty required field(s)');
        }
        return parent::callbackInit($request);
    }

    /**
     * Обработка вызовов платежной системы.
     *
     * Проверяются параметры запроса, и при необходимости вызывается обработчик приложения.
     * Настройки плагина уже проинициализированы и доступны в коде метода.
     *
     *
     * @param array $request Данные запроса (массив $_REQUEST), полученного от платежной системы
     * @throws waPaymentException
     * @return array Ассоциативный массив необязательных параметров результата обработки вызова:
     *     'redirect' => URL для перенаправления пользователя
     *     'template' => путь к файлу шаблона, который необходимо использовать для формирования веб-страницы, отображающей результат обработки вызова платежной системы;
     *                   укажите false, чтобы использовать прямой вывод текста
     *                   если не указано, используется системный шаблон, отображающий строку 'OK'
     *     'header'   => ассоциативный массив HTTP-заголовков (в форме 'header name' => 'header value'),
     *                   которые необходимо отправить в браузер пользователя после завершения обработки вызова,
     *                   удобно для случаев, когда кодировка символов или тип содержимого отличны от UTF-8 и text/html
     *
     *     Если указан путь к шаблону, возвращаемый результат в исходном коде шаблона через переменную $result variable;
     *     параметры, переданные методу, доступны в массиве $params.
     */
    protected function callbackHandler($request)
    {
        // приводим данные о транзакции к универсальному виду
        $transaction_data = $this->formalizeData($request);

        // проверяем поддержку типа указанный транзакции данным плагином
        if (!in_array($transaction_data['type'], $this->supportedOperations())) {
            self::log($this->id, array('error' => 'unsupported payment operation'));
            throw new waPaymentException('Unsupported payment operation');
        }
        if (!$this->LMI_MERCHANT_ID) {
            throw new waPaymentException('Empty merchant data');
        }

        // определяем способ обработки транзакции приложением в зависимости от типа транзакции
        switch ($transaction_data['type']) {
            case self::OPERATION_CHECK:
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                $transaction_data['state'] = self::STATE_AUTH;
                break;

            case self::OPERATION_AUTH_CAPTURE:
            default:
                if (!$this->verifySign($request)) {
                    self::log($this->id, array('error' => 'invalid hash'));
                    throw new waPaymentException('Invalid hash', 403);
                }
                //TODO log payer WM ID
                $app_payment_method = self::CALLBACK_PAYMENT;
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;
        }

        // сохраняем данные транзакции в базу данных
        $transaction_data = $this->saveTransaction($transaction_data, $request);

        $transaction_data['success_back_url'] = ifset($request['wa_success_url']);

        // вызываем соответствующий обработчик приложения для каждого из поддерживаемых типов транзакций
        $result = $this->execAppCallback($app_payment_method, $transaction_data);

        // в зависимости от успешности или неудачи обработки транзакции приложением отображаем сообщение либо отправляем соответствующий HTTP-заголовок
        // информацию о результате обработки дополнительно пишем в лог плагина
        if (!empty($result['result'])) {
            self::log($this->id, array('result' => 'success'));
            $message = 'YES';
        } else {
            $message = !empty($result['error']) ? $result['error'] : 'wa transaction error';
            self::log($this->id, array('error' => $message));
            header("HTTP/1.0 403 Forbidden");
        }
        echo $message;
        exit;
    }

    /**
     * Возвращает URL запроса к платежной системе в зависимости от выбранного в настройках протокола подключения.
     *
     * @return string
     */
    protected function getEndpointUrl()
    {
        switch ($this->protocol) {
            case self::PROTOCOL_WEBMONEY_LEGACY_COM:
            case self::PROTOCOL_PAYMASTER_COM:
                $url = 'https://psp.paymaster24.com/Payment/Init';
                break;
            case self::PROTOCOL_WEBMONEY_LEGACY:
            case self::PROTOCOL_PAYMASTER:
                $url = 'https://paymaster.ru/Payment/Init';
                break;
            case self::PROTOCOL_WEBMONEY:
            default:
                $url = 'https://merchant.webmoney.ru/lmi/payment.asp';

                break;
        }
        return $url;
    }

    private function getFormOptions()
    {
        $options = array();
        switch ($this->protocol) {
            case self::PROTOCOL_WEBMONEY:
            default:
                $options['accept-charset'] = 'windows-1251';
                break;
        }
        return $options;

    }

    private function verifySign($data)
    {
        $result = false;
        switch ($this->protocol) {
            case self::PROTOCOL_PAYMASTER:
            case self::PROTOCOL_PAYMASTER_COM:
                /**
                 * Check user sign
                 * base64
                 * md5
                 */
                $fields = array(
                    /*01.Идентификатор Компании (LMI_MERCHANT_ID);*/
                    'LMI_MERCHANT_ID',
                    /*02.Внутренний номер покупки продавца (LMI_PAYMENT_NO);*/
                    'LMI_PAYMENT_NO',
                    /*03.Номер платежа в системе Paymaster (LMI_SYS_PAYMENT_ID);*/
                    'LMI_SYS_PAYMENT_ID',
                    /*04.Дата платежа (LMI_SYS_PAYMENT_DATE);*/
                    'LMI_SYS_PAYMENT_DATE',
                    /*05.Сумма платежа, заказанная Компанией (LMI_PAYMENT_AMOUNT);*/
                    'LMI_PAYMENT_AMOUNT',
                    /* 06.Валюта платежа, заказанная Компанией (LMI_CURRENCY);*/
                    'LMI_CURRENCY',
                    /* 07.Сумма платежа в валюте, в которой покупатель производит платеж (LMI_PAID_AMOUNT);*/
                    'LMI_PAID_AMOUNT',
                    /* 08.Валюта, в которой производится платеж (LMI_PAID_CURRENCY)*/
                    'LMI_PAID_CURRENCY',
                    /* 09.Идентификатор платежной системы, выбранной покупателем (LMI_PAYMENT_SYSTEM)*/
                    'LMI_PAYMENT_SYSTEM',
                    /* 10.Флаг тестового режима (LMI_SIM_MODE)*/
                    'LMI_SIM_MODE',

                );
                $hash_string = '';
                foreach ($fields as $field) {
                    $hash_string .= (isset($data[$field]) ? $data[$field] : '').';';
                }
                /**
                 *  11.Secret Key
                 */
                $hash_string .= $this->secret_key;
                if ($this->hash_method == 'md5') {
                    $transaction_hash = base64_encode(md5($hash_string, true));
                } else if ($this->hash_method == 'sha') {
                    $transaction_hash = base64_encode(sha1($hash_string, true));
                } else {
                    if (function_exists('hash') && function_exists('hash_algos') && in_array('sha256', hash_algos())) {
                        $transaction_hash = base64_encode(hash('sha256', $hash_string, true)); 
                    } else {
                        throw new waException('sha256 not supported');
                    }
                }
                unset($hash_string);

                $transaction_sign = isset($data['LMI_HASH']) ? $data['LMI_HASH'] : null;

                break;
            case self::PROTOCOL_WEBMONEY_LEGACY:
            case self::PROTOCOL_WEBMONEY_LEGACY_COM:
            case self::PROTOCOL_WEBMONEY:
            default:
                /**
                 * Check user sign
                 * md5
                 */
                $fields = array(
                    /* 1.Кошелек продавца (LMI_PAYEE_PURSE);*/
                    'LMI_PAYEE_PURSE',
                    /* 2.Сумма платежа (LMI_PAYMENT_AMOUNT);*/
                    'LMI_PAYMENT_AMOUNT',
                    /* 3.Внутренний номер покупки продавца (LMI_PAYMENT_NO);*/
                    'LMI_PAYMENT_NO',
                    /* 4.Флаг тестового режима (LMI_MODE);*/
                    'LMI_MODE',
                    /* 5.Внутренний номер счета в системе WebMoney Transfer (LMI_SYS_INVS_NO);*/
                    'LMI_SYS_INVS_NO',
                    /* 6.Внутренний номер платежа в системе WebMoney Transfer (LMI_SYS_TRANS_NO);*/
                    'LMI_SYS_TRANS_NO',
                    /* 7.Дата и время выполнения платежа (LMI_SYS_TRANS_DATE);*/
                    'LMI_SYS_TRANS_DATE',
                    /* 8.Secret Key (LMI_SECRET_KEY);*/
                    'LMI_SECRET_KEY',
                    /* 9.Кошелек покупателя (LMI_PAYER_PURSE);*/
                    'LMI_PAYER_PURSE',
                    /* 10.WMId покупателя (LMI_PAYER_WM).*/
                    'LMI_PAYER_WM',
                );
                $data['LMI_SECRET_KEY'] = $this->secret_key;
                $hash_string = '';
                foreach ($fields as $field) {
                    $hash_string .= (isset($data[$field]) ? $data[$field] : '');
                }

                if ($this->hash_method == 'md5') {
                    $transaction_hash = strtolower(md5($hash_string));
                } else {
                    if (function_exists('hash') && function_exists('hash_algos') && in_array('sha256', hash_algos())) {
                        $transaction_hash = strtolower(hash('sha256', $hash_string));
                    } else {
                        throw new waException('sha256 not supported');
                    }
                }
                unset($data['LMI_SECRET_KEY']);
                unset($hash_string);

                $transaction_sign = isset($data['LMI_HASH']) ? strtolower($data['LMI_HASH']) : null;

                break;
        }

        if (!empty($data['LMI_PREREQUEST']) || ($transaction_sign == $transaction_hash)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Конвертирует исходные данные о транзакции, полученные от платежной системы, в формат, удобный для сохранения в базе данных.
     *
     * @param array $request Исходные данные
     * @return array $transaction_data Форматированные данные
     */
    protected function formalizeData($request)
    {
        // формируем полный список полей, относящихся к транзакциям, которые обрабатываются платежной системой WebMoney
        $fields = array(
            'LMI_MERCHANT_ID',
            'LMI_PAYMENT_NO',
            'LMI_PAYMENT_AMOUNT',
            'LMI_CURRENCY',
            'LMI_PAID_AMOUNT',
            'LMI_PAID_CURRENCY',
            'LMI_PAYMENT_SYSTEM',
            'LMI_SYS_INVS_NO',
            'LMI_SYS_TRANS_NO',
            'LMI_SIM_MODE',
            'LMI_PAYMENT_DESC',
            'wa_app',
            'wa_merchant_contact_id',
            'LMI_PREREQUEST',
            'LMI_HASH',
            'LMI_SYS_PAYMENT_ID',
            'LMI_SYS_PAYMENT_DATE',
        );
        foreach ($fields as $f) {
            if (!isset($request[$f])) {
                $request[$f] = null;
            }
        }

        // выполняем базовую обработку данных
        $transaction_data = parent::formalizeData($request);


        // добавляем дополнительные данные:

        // тип транзакции
        $transaction_data['type'] = !empty($request['LMI_PREREQUEST']) ? self::OPERATION_CHECK : (!empty($request['LMI_HASH']) ? self::OPERATION_AUTH_CAPTURE : 'N/A');

        // идентификатор транзакции, присвоенный платежной системой
        if (!$request['LMI_SYS_PAYMENT_ID'] && ($request['LMI_SYS_INVS_NO'] || $request['LMI_SYS_TRANS_NO'])) {
            $transaction_data['native_id'] = $request['LMI_SYS_INVS_NO'].':'.$request['LMI_SYS_TRANS_NO'];
        } else {
            $transaction_data['native_id'] = $request['LMI_SYS_PAYMENT_ID'];
        }

        // номер заказа
        $transaction_data['order_id'] = $request['LMI_PAYMENT_NO'];

        // сумма заказа
        $transaction_data['amount'] = $request['LMI_PAYMENT_AMOUNT'];

        // идентификатор валюты заказа
        $transaction_data['currency_id'] = $request['LMI_CURRENCY'];
        if (empty($transaction_data['currency_id'])) {
            $currency = $this->allowedCurrency();
            if ($currency && !is_array($currency)) {
                $transaction_data['currency_id'] = $currency;
            }
        }

        if ((int)ifset($request['LMI_MODE'])) {
            $transaction_data['view_data'] = ' ТЕСТОВЫЙ ЗАПРОС';
        }

        return $transaction_data;
    }

    /**
     * Возвращает список операций с транзакциями, поддерживаемых плагином.
     *
     * @see waPayment::supportedOperations()
     * @return array
     */
    public function supportedOperations()
    {
        return array(
            self::OPERATION_CHECK,
            self::OPERATION_AUTH_CAPTURE,
        );
    }

    public static function _getProtocols()
    {
        $protocols = array();
        $protocols[] = array(
            'title' => 'подключение к WebMoney',
            'value' => self::PROTOCOL_WEBMONEY,
        );
        $protocols[] = array(
            'title' => 'подключение к PayMaster (режим совместимости)',
            'value' => self::PROTOCOL_WEBMONEY_LEGACY,
        );
        $protocols[] = array(
            'title' => 'подключение к PayMaster',
            'value' => self::PROTOCOL_PAYMASTER,
        );
        $protocols[] = array(
            'title' => 'подключение к Paymaster24 (режим совместимости)',
            'value' => self::PROTOCOL_WEBMONEY_LEGACY_COM,
        );
        $protocols[] = array(
            'title' => 'подключение к Paymaster24',
            'value' => self::PROTOCOL_PAYMASTER_COM,
        );
        return $protocols;
    }
}
