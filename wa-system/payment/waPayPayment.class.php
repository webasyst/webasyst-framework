<?php
/**
 * This implements a special built-in payment plugin id=pay.
 * @since 4.0.0
 */
class waPayPayment extends waPayment implements waIPayment, waIPaymentMultipleOptions, waIPaymentImage, waIPaymentRefund
{
    /** Called by waPayment  */
    protected static function waPayPluginInfo()
    {
        $result = [];
        $installer_zone = self::getGeoZone();
        switch ($installer_zone) {
            case 'ru':
                $result = [
                    'name' => 'СБП или картой',
                    'description' => 'Мгновенная онлайн-оплата по QR-коду СБП или банковской картой.',
                ];
                break;
        }

        return $result + [
            'name'                => 'Webasyst Pay',
            'description'         => '',
            'icon'                => [
                48 => wa()->getRootUrl().'wa-content/img/payment/sbp.svg?1',
                24 => wa()->getRootUrl().'wa-content/img/payment/sbp.svg?2',
                16 => wa()->getRootUrl().'wa-content/img/payment/sbp.svg?3',
            ],
            'logo'                => wa()->getRootUrl().'wa-content/img/payment/sbp.svg?4',
            'img'                 => wa()->getRootUrl().'wa-content/img/payment/spb.svg?5',
            'version'             => wa()->getVersion('webasyst'),
            'vendor'              => 'webasyst',
            'type'                => waPayment::TYPE_CARD,
            'fractional_quantity' => true,
            'stock_units'         => true,
            'partial_refund'      => true,
        ];
    }

    // same as lib/config/settings.php in normal payment plugin
    protected function config()
    {
        if ($this->config === null) {
            $this->config = [

                // this selector will be hidden in DOM by JS and replaced with custom tabs
                'provider' => [
                    'value'        => 'empty',
                    'title'        => 'Шлюз',
                    'description'  => '',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => 'tbank', 'title' => 'Т-Банк'],
                        ['value' => 'yookassa', 'title' => 'ЮКасса'],
                        ['value' => 'empty', 'title' => 'Выберу позже'],
                    ],
                ],

                //
                // Fields for provider='yookassa'
                //
                'shop_id' => [
                    'value'        => '',
                    'title'        => 'Идентификатор магазина',
                    'description'  => 'Выдается ЮКассой после подключения.',
                    'control_type' => waHtmlControl::INPUT,
                    'class'        => ['field-provider-specific', 'provider-yookassa', 'required'],
                ],
                'shop_password' => [
                    'value'        => '',
                    'title'        => 'Секретный ключ',
                    'control_type' => waHtmlControl::INPUT,
                    'class'        => ['field-provider-specific', 'provider-yookassa', 'required'],
                    'description'  => <<<HTML
<span class="js-yandexkassa-registration-link" style="background-color: #e3ffc8; display: block; margin: 10px 0; padding: 10px 15px; font-weight: normal; font-size: 14px;color: black; width: 80%; border-radius: 8px;">
Подключаясь к ЮКассе <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=f799812face0b887237ea5609bd49a7fef" target="_blank" style="color: #09f;"><b>через Webasyst по этой ссылке</b></a>, вы получаете <b>премиум-тариф со ставками от&nbsp;2,8%</b> на 3&nbsp;месяца.
</span>
<span class="js-yandexkassa-registration-link" style="font-weight: normal; font-size: 14px;color: black;">
Чтобы получить shopID и ключ, <a href="https://www.webasyst.com/my/ajax/?action=campain&hash=f799812face0b887237ea5609bd49a7fef" target="_blank">отправьте заявку на подключение</a>.
</span>
<br><br>
HTML
                ],
                'receipt' => [
                    'value'        => '',
                    'title'        => 'Формировать чек оплаты',
                    'description'  => 'Если включена фискализация, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги'
            .'<br>'
            .'— налог составляет 0%, 5%, 7%, 10%, 20% либо 22% и <em>включён</em> в стоимость элементов заказа и стоимость доставки',
                    'control_type' => waHtmlControl::CHECKBOX,
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'payment_subject_type_product' => [
                    'value'        => 'commodity',
                    'title'        => 'Предмет расчёта в чеках для товаров',
                    'description'  => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getTbankProductTypes()),
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'payment_subject_type_service' => [
                    'value'        => 'service',
                    'title'        => 'Предмет расчёта в чеках для услуг',
                    'description'  => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getTbankProductTypes()),
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'payment_subject_type_shipping' => [
                    'value'        => 'service',
                    'title'        => 'Предмет расчёта в чеках для доставки',
                    'description'  => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getTbankProductTypes()),
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'payment_method_type' => [
                    'value'        => 'full_payment',
                    'title'        => 'Признак способа расчета в чеках',
                    'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getPaymentMethodTypes()),
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'taxes' => [
                    'value'        => 'no',
                    'title'        => 'Передача ставок НДС',
                    'description'  => 'Если ваша организация работает по ОСН, выберите вариант «Передавать ставки НДС по каждой позиции».<br>
Ставка НДС может быть равна 0%, 5%, 7%, 10%, 20% или 22%. В настройках налогов в приложении выберите, чтобы НДС был включён в цену товара.<br>
Если вы работаете по другой системе налогообложения, выберите «НДС не облагается».',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => 'no', 'title' => 'НДС не облагается'],
                        ['value' => 'map', 'title' => 'Передавать ставки НДС по каждой позиции'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'tax_system_code' => [
                    'value'        => '0',
                    'title'        => 'Код системы налогообложения',
                    'description'  => 'Параметр <code>taxSystem</code>. Выберите нужное значение, только если вы используете несколько систем налогообложения.
В остальных случаях оставьте вариант «Не передавать».',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => '0', 'title' => 'Не передавать'],
                        ['value' => '1', 'title' => 'Общая СН'],
                        ['value' => '2', 'title' => 'Упрощенная СН (доходы)'],
                        ['value' => '3', 'title' => 'Упрощенная СН (доходы минус расходы)'],
                        ['value' => '4', 'title' => 'Единый налог на вмененный доход'],
                        ['value' => '5', 'title' => 'Единый сельскохозяйственный налог'],
                        ['value' => '6', 'title' => 'Патентная СН'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'merchant_currency' => [
                    'value'        => 'RUB',
                    'title'        => 'Валюта',
                    'description'  => 'Выберите валюту, отличную от российского рубля, чтобы принимать платежи в этой валюте.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => 'RUB', 'title' => 'RUB'],
                        ['value' => 'USD', 'title' => 'USD'],
                        ['value' => 'EUR', 'title' => 'EUR'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],
                'manual_capture' => [
                    'value'        => '',
                    'title'        => 'Использовать двухстадийную оплату',
                    'description'  => '',
                    'control_type' => waHtmlControl::CHECKBOX,
                    'class'        => ['field-provider-specific', 'provider-yookassa'],
                ],

                //
                // Fields for provider='tbank'
                //

                'terminal_key' => [
                    'value'        => '',
                    'title'        => 'Terminal ID',
                    'description'  => 'Выдается Т-Кассой после подключения.',
                    'control_type' => waHtmlControl::INPUT,
                    'class'        => ['field-provider-specific', 'provider-tbank', 'required'],
                ],
                'terminal_password' => [
                    'value'        => '',
                    'title'        => 'Пароль',
                    'control_type' => waHtmlControl::INPUT,
                    'class'        => ['field-provider-specific', 'provider-tbank', 'required'],
                    'description'  => <<<HTML
<span class="js-tkassa-registration-link" style="background-color: #e3ffc8; display: block; margin: 10px 0; padding: 10px 15px; font-weight: normal; font-size: 14px;color: black; width: 80%; border-radius: 8px;">
Подключайтесь к Т-Кассе <b><a href="https://www.tbank.ru/kassa/?utm_source=partners_sme&utm_medium=prt.utl&utm_campaign=business.int_acquiring.5-3AKNBMR5&partnerId=5-3AKNBMR5&agentId=1-5UKK6AD&agentSsoId=716fa180-4245-46d4-bff0-eb2926d52c32" target="_blank" style="color: #09f;">через Webasyst по этой ссылке</a> и получите ставку 2,7% с дальнейшим понижением</b>. Данные для заполнения Terminal ID и пароля будут выданы сразу после подключения.
</span>
HTML
                ],
                'currency_id' => [
                    'value'        => 'RUB',
                    'title'        => 'Валюта',
                    'description'  => 'Валюта, в которой будут выполняться платежи',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => 'RUB', 'title' => 'RUB'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'two_steps' => [
                    'value'        => '',
                    'title'        => 'Использовать двухстадийную оплату',
                    'description'  => 'Вариант обработки платежей, выбранный при заключении договора с Т-Кассой.<br>Двухстадийную схему подключения можно использовать только с поддерживаемым приложением, например, Shop-Script версии не ниже 8.6.',
                    'control_type' => waHtmlControl::CHECKBOX,
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'check_data_tax' => [
                    'value'        => '',
                    'title'        => 'Формировать чек оплаты',
                    'description'  => 'Если включена интеграция с онлайн-кассами, то клиенты смогут использовать этот способ оплаты только в следующих случаях:'
            .'<br>'
            .'— к элементам заказа и стоимости доставки не применяются налоги;'
            .'<br>'
            .'— налог составляет 0%, 5%, 7%, 10%, 20% либо 22% и <em>включен</em> в стоимость позиций заказа и стоимость доставки.',
                    'control_type' => waHtmlControl::CHECKBOX,
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'taxation' => [
                    'value'        => '0',
                    'title'        => 'Тип налогообложения',
                    'description'  => '',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => 'osn', 'title' => 'Общая СН'],
                        ['value' => 'usn_income', 'title' => 'Упрощенная СН (доходы)'],
                        ['value' => 'usn_income_outside', 'title' => 'Упрощенная СН (доходы минус расходы)'],
                        ['value' => 'envd', 'title' => 'Единый налог на вмененный доход'],
                        ['value' => 'esn', 'title' => 'Единый сельскохозяйственный налог'],
                        ['value' => 'patent', 'title' => 'Патентная СН'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'payment_object_type_product' => [
                    'value'        => 'commodity',
                    'title'        => 'Предмет расчёта в чеках для товаров',
                    'description'  => 'Категория ваших товаров в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getTbankProductTypes()),
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'payment_object_type_service' => [
                    'value'        => 'service',
                    'title'        => 'Предмет расчёта в чеках для услуг',
                    'description'  => 'Категория ваших услуг для товаров в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getTbankProductTypes()),
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'payment_object_type_shipping' => [
                    'value'        => 'service',
                    'title'        => 'Предмет расчёта в чеках для доставки',
                    'description'  => 'Категория услуги по доставке заказа в чеке — для передачи в налоговую инспекцию.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getTbankProductTypes()),
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'payment_method_type_tbank' => [
                    'value'        => 'full_payment',
                    'title'        => 'Признак способа расчета в чеках',
                    'description'  => 'Категория способа оплаты всех позиций в чеке — для передачи в налоговую инспекцию',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => array_map(function($value) {
                        return ['value' => $value, 'title' => $value];
                    }, $this->getPaymentMethodTypes()),
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'payment_ffd' => [
                    'value'        => '1.2',
                    'title'        => 'Версия ФФД',
                    'description'  => 'Текущая выбранная версия должна совпадать с версией в настройках ОФД.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => '1.05', 'title' => '1.05'],
                        ['value' => '1.2', 'title' => '1.2'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'payment_language' => [
                    'value'        => 'ru',
                    'title'        => 'Язык платежной формы',
                    'description'  => 'Выберите язык платежной формы для своих клиентов.',
                    'control_type' => waHtmlControl::SELECT,
                    'options'      => [
                        ['value' => 'ru', 'title' => 'Русский'],
                        ['value' => 'en', 'title' => 'Английский'],
                    ],
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],
                'testmode' => [
                    'value'        => '',
                    'title'        => 'Тестовый режим',
                    'description'  => 'Только для тестирования по старой схеме через платежный шлюз <em>https://rest-api-test.tinkoff.ru/rest/</em>.',
                    'control_type' => waHtmlControl::CHECKBOX,
                    'class'        => ['field-provider-specific', 'provider-tbank'],
                ],

                //
                // Fields for provider='empty'
                //
                'text' => [
                    'value'        => 'Спасибо за заказ!
Пожалуйста, свяжитесь с менеджером магазина по поводу оплаты.',
                    'title'        => 'Текст об оплате',
                    'description'  => 'Будет показан покупателю вместо QR-кода на оплату.',
                    'class'        => ['field-provider-specific', 'provider-empty'],
                    'control_type' => waHtmlControl::TEXTAREA,
                ],

                // storage for callback secret hash required for validation
                'callback_secret' => [
                    'value'        => '',
                    'title'        => '',
                    'description'  => '',
                    'control_type' => waHtmlControl::HIDDEN,
                ],
            ];
        }
        return $this->config;
    }

    public function getSettingsHTML($params = array())
    {
        $installer_zone = self::getGeoZone();

        $last_save_response = $this->getSettings('last_save_response');

        $fields_html = parent::getSettingsHTML($params);

        $view = wa('webasyst')->getView();
        $view->assign([
            //'last_save_response' => $last_save_response,
            'installer_zone' => $installer_zone,
            'fields_html' => $fields_html,
            'is_new' => !is_numeric($this->getPluginKey()),
        ]);
        return $view->fetch(wa()->getConfig()->getRootPath() .'/wa-system/payment/templates/waPaySettings.html');
    }

    public function getGuide($params = array())
    {
        if (SystemConfig::isDebug()) {
            return <<<HTML
                <span class="small" id="js-toggle-debug-info"><i class="fas fa-caret-right"></i><i class="fas fa-caret-down hidden"></i> Webasyst Pay communication debug</span>
                <script>(function(){
                    const header = $('#js-toggle-debug-info').click(function() {
                        header.siblings('pre').toggleClass('hidden');
                        header.find('.fa-caret-down,.fa-caret-right').toggleClass('hidden');
                    });
                }());</script>
HTML
            .'<pre class="hidden">'.wa_dump_helper(ref($this->getSettings('last_save_response'))).'</pre>';
        }
    }

    /**
     * Called by app when user saves plugin settings. Plugin is expected to post-process and
     * save its settings either via $this->getAdapter()->setSettings($this->id, $this->key, $name, $value)
     * or plugin's own storage (e.g. DB table).
     *
     * @param array $settings   form fields rendered by $this->getSettingsHTML()
     */
    public function saveSettings($settings = array())
    {
        switch (ifset($settings, 'provider', 'empty')) {
            case 'tbank':
                $defaults = [
                    'terminal_key' => '',
                    'terminal_password' => '',
                    'currency_id' => '',
                    'two_steps' => 0,
                    'check_data_tax' => 0,
                    'taxation' => '',
                    'payment_object_type_product' => '',
                    'payment_object_type_service' => '',
                    'payment_object_type_shipping' => '',
                    'payment_method_type_tbank' => '',
                    'payment_ffd' => '',
                    'payment_language' => '',
                    'testmode' => 0,
                ];
                $api_save_request = [
                    'provider' => 'tbank',
                    'settings' => array_intersect_key($settings + $defaults, $defaults),
                ];
                break;
            case 'yookassa':
                $defaults = [
                    'shop_id' => '',
                    'shop_password' => '',
                    'receipt' => 0,
                    'payment_subject_type_product' => '',
                    'payment_subject_type_service' => '',
                    'payment_subject_type_shipping' => '',
                    'payment_method_type' => '',
                    'taxes' => '',
                    'tax_system_code' => '',
                    'merchant_currency' => '',
                    'manual_capture' => 0,
                ];
                $api_save_request = [
                    'provider' => 'yookassa',
                    'settings' => array_intersect_key($settings + $defaults, $defaults),
                ];
                break;
            default: // empty
                $settings['provider'] = 'empty';
                $api_save_request = [
                    'provider' => 'empty',
                    'settings' => [
                        'text' => ifset($settings, 'text', ''),
                    ],
                ];
        }

        // Save selected provider to Webasyst Pay server.
        // If something goes wrong, remember the error in app storage to show later in getSettingsHTML()
        $api_save_request['callback_url'] = $this->getRelayUrl();
        $response = $this->apiQuery('PAY_SETTINGS', '', $api_save_request, waNet::METHOD_PUT);
        $settings['last_save_response'] = $response;
        $settings['allowed_currency'] = ifset($response, 'response', 'allowed_currency', true);
        $settings['do_fiscalization'] = ifset($response, 'response', 'do_fiscalization', null);

        $new_callback_secret = ifset($response, 'response', 'callback_secret', null);
        if ($new_callback_secret) {
            $settings['callback_secret'] = $new_callback_secret;
        }
        if (empty($settings['callback_secret'])) {
            $response = $this->apiQuery('PAY_SETTINGS', '', [
                'callback_secret' => true,
            ], waNet::METHOD_PATCH);
            $settings['last_save_response']['additional PATCH request'] = $response;
            $settings['callback_secret'] = ifset($response, 'response', 'callback_secret', null);
        }

        // Save settings of all providers to app storage
        return parent::saveSettings($settings);
    }

    public function paymentOptions($order_data): array
    {
        $provider = $this->getSettings('provider');
        if ($provider === 'empty') {
            $text = $this->getSettings('text');
            if (!$text) {
                return [];
            }
            return [[
                'name' => $text,
                'description' => '',
                //'logo' => ifset($opt, 'logo', $m['logo']),
                'payment_form_data' => [],
            ]];
        }

        return [[
            'name' => 'Оплатить картой',
            'description' => 'МИР, Visa, MasterCard, SberPay, T-Pay, карты российских банков',
            //'logo' => ifset($opt, 'logo', $m['logo']),
            'payment_form_data' => [],
        ]];
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $request_data = $this->getRequestDataPaymentInit($order_data, $payment_form_data);
        $response = $this->apiQuery('PAY', 'payment-url', $request_data, waNet::METHOD_POST);

        // already paid?..
        $error = ifset($response, 'response', 'error', null);
        if ($response['status'] == 409) {
            if ($error == 'already_paid') {
                $this->handlePayment($order_data['id'], $order_data['total'], $order_data['currency'], !!$this->getSettings('do_fiscalization'));
            //} else if ($error == 'already_in_progress') {
            //} else if ($error == 'already_refunded') {
            }
            return 'Состояние платежа изменилось — обновите страницу.';
        }

        $provider = $this->getSettings('provider');
        if ($provider === 'empty') {
            $text = $this->getSettings('text');
            if ($text) {
                return $text;
            }
        }

        if ($error) {
            return ifempty($response, 'response', 'error_description', $error);
        }

        $payment_url = ifset($response, 'response', 'payment_url', null);
        if ($payment_url) {
            if ($auto_submit) {
                return '<script>window.location = '.json_encode($payment_url).';</script>';
            } else {
                $button_text = 'Оплатить заказ';
                return <<<EOF
                    <form action="{$payment_url}" method="get" target="_top">
                        <input type="submit" value="{$button_text}" />
                    </form>
EOF;
            }
        }

        return ifset($response, 'response', 'text', 'Способ оплаты Webasyst Pay не настроен.');
    }

    public function image($order_data)
    {
        $request_data = $this->getRequestDataPaymentInit($order_data);
        $response = $this->apiQuery('PAY', 'sbp', $request_data, waNet::METHOD_POST);

        $payment_url = ifset($response, 'response', 'qr_image', null);
        if ($payment_url) {
            $result = [
                'name' => 'СБП',
                'description' => 'Отсканируйте QR-код в приложении своего банка для быстрой оплаты.',
                'image_data_url' => $payment_url,
            ];
            $payload = ifset($response, 'response', 'qr_payload', null);
            if ($payload) {
                // for SBP this is URL like https://qr.nspk.ru/Axxxxx
                $result['qr_payload'] = $payload;
            }
            return $result;
        }

        $error = ifempty($response, 'response', 'error_description', ifset($response, 'response', 'error', null));
        $error = ifempty($error, 'Способ оплаты Webasyst Pay не настроен.');
        throw new waException($error);
    }

    public function refund($transaction_raw_data)
    {
        $transaction = $transaction_raw_data['transaction'];
        $refund_amount = $transaction_raw_data['refund_amount'];
        $refund_amount = $refund_amount === true ? $transaction['amount'] : $refund_amount;
        $is_full_refund = $refund_amount >= $transaction['amount'];
        $request_data = $this->getRequestDataRefund($refund_amount, $transaction, ifset($transaction_raw_data, 'refund_items', null));
        $response = $this->apiQuery('PAY', 'refund', $request_data, waNet::METHOD_POST);
        if ($response['status'] != 204 && $response['status'] != 200) {
            self::log($this->id, [
                'Ошибка при попытке выполнить возврат через API Webasyst Pay.',
                'method' => __METHOD__,
                'request' => $request_data,
                'response' => $response,
            ]);
            $error = ifempty($response, 'response', 'error_description', ifset($response, 'response', 'error', null));
            $error = ifempty($error, 'Ошибка при попытке выполнить возврат через API Webasyst Pay.');
            return [
                'result'      => -1,
                'data'        => $response,
                'description' => $error,
            ];
        }

        $now = date('Y-m-d H:i:s');
        $refund_transaction = [
            'native_id'       => $transaction['native_id'],
            'type'            => self::OPERATION_REFUND,
            'state'           => $is_full_refund ? self::STATE_REFUNDED : self::STATE_PARTIAL_REFUNDED,
            'result'          => 1,
            'order_id'        => $transaction['order_id'],
            'customer_id'     => $transaction['customer_id'],
            'amount'          => $refund_amount,
            'currency_id'     => $transaction['currency_id'],
            'parent_id'       => $transaction['id'],
            'create_datetime' => $now,
            'update_datetime' => $now,
        ];
        $this->saveTransaction($refund_transaction);
        return [
            'result'      => 0,
            'data'        => null,
            'description' => '',
        ];
    }

    protected function getRequestDataRefund($refund_amount, $transaction, ?array $items=null)
    {
        $request_data = [
            'order_id' => $transaction['native_id'],
            'refund_amount' => $refund_amount === true ? $transaction['amount'] - ifset($transaction, 'refunded_amount', 0) : $refund_amount,
        ];
        if ($items) {
            $request_data['refund_items'] = $this->getRequestDataItems($items);
        }
        return $request_data;
    }

    protected function getRequestDataPaymentInit($order_data, $payment_form_data=[])
    {
        $request_data = [
            'id' => $order_data['id'],
            'amount' => $order_data['total'],
            'discount' => $order_data['discount'],
            'tax' => $order_data['tax'],
            'tax_included' => (bool) $order_data['tax_included'],
            'shipping' => $order_data['shipping'],
            'shipping_tax_rate' => $order_data['shipping_tax_rate'],
            'shipping_tax_included' => (bool) $order_data['shipping_tax_included'],
            'subtotal' => $order_data['subtotal'],
            'currency_id' => $order_data['currency'],
            'description' => $order_data['description'],
        ];

        $sales_channel = ifset($order_data, 'params', 'sales_channel', null);
        if ($this->app_id === 'shop' && $sales_channel) {
            [$channel_type, $channel_id] = explode(':', $sales_channel, 2) + ['', null];
            $request_data['app_platform'] = $channel_type;
            if ($channel_id && wa_is_int($channel_id)) {
                $request_data['app_channel_id'] = 'shop-'.$channel_id;
            }
        }

        try {
            if (!empty($order_data['customer_contact_id'])) {
                $c = new waContact($order_data['customer_contact_id']);
                $email = $c->get('email', 'default');
                $phone = $c->get('phone', 'default');
            }
        } catch (waException $e) {
            // contact is deleted
        }
        if (!empty($email)) {
            $request_data['customer']['email'] = $email;
        }
        if (!empty($phone)) {
            $request_data['customer']['phone'] = $phone;
        }
        if (wa()->getEnv() == 'frontend') {
            $request_data['customer_ip'] = waRequest::getIp();
        }

        $request_data['items'] = $this->getRequestDataItems($order_data['items'], (bool) $request_data['tax_included']);
        return $request_data;
    }

    protected function getRequestDataItems(array $items, bool $default_tax_included=true)
    {
        $result = [];
        foreach ($items as $item) {
            $item['tax_rate'] = ifset($item, 'tax_rate', 0);
            if ($item['tax_rate']) {
                $item['tax_included'] = (bool) ifset($item, 'tax_included', $default_tax_included);
            } else {
                $item['tax_included'] = $default_tax_included;
            }
            $result[] = array_intersect_key($item, [
                'name' => '',
                'sku' => '',
                'tax_rate' => NULL,
                'tax_included' => '0',
                //'description' => '',
                'price' => 0.0,
                'quantity' => 0,
                'total' => 0.0,
                'type' => 'product',
                //'product_id' => '1',
                //'sku_id' => '1',
                //'weight' => 0.0,
                //'height' => 0.0,
                //'length' => 0.0,
                //'width' => 0.0,
                //'weight_unit' => 'kg',
                //'dimensions_unit' => 'm',
                'stock_unit' => 'шт.',
                //'stock_unit_code' => 0,
                //'total_discount' => 0,
                //'discount' => 0,
                //'product_codes' => [],
            ]);
        }
        return $result;
    }

    protected function apiQuery($endpoint, $subpath, $content, $method)
    {
        $response = (new waServicesApi())->serviceCall($endpoint, $content, $method, [], $this->app_id.'/'.$this->merchant_id.'/'.$subpath);
        return $response;
    }

    protected static function getGeoZone()
    {
        try {
            wa('installer');
            return installerHelper::getGeoZone();
        } catch (Throwable $e) {
            return substr(wa()->getLocale(), 0, 2);
        }
    }

    protected function getTbankProductTypes()
    {
        return [
            'commodity',
            'excise',
            'service',
            'gambling_bet',
            'gambling_prize',
            'lottery',
            'lottery_prize',
            'intellectual_activity',
            'payment',
            'agent_commission',
            'property_right',
            'non_operating_gain',
            'insurance_premium',
            'sales_tax',
            'resort_fee',
            'composite',
            'another',
        ];
    }

    protected function getPaymentMethodTypes()
    {
        return [
            'full_prepayment',
            'prepayment',
            'advance',
            'full_payment',
            'partial_payment',
            'credit',
            'credit_payment',
        ];
    }

    public function allowedCurrency()
    {
        $result = $this->getSettings('allowed_currency');
        if (empty($result) || $result === '1') {
            $result = true;
        }
        return $result;
    }

    /**
     * Notifies application about a successfull payment.
     * Called after an API request when it turns out that order is already paid.
     * This happens during a callback or during attempt to initialize payment.
     */
    protected function handlePayment($order_id, $api_order_paid_amount, $currency, $do_fiscalization)
    {
        $transaction = $transaction_data = $this->makeWaTransactionRow($order_id, $api_order_paid_amount, $currency);
        $transaction['type'] = self::OPERATION_CAPTURE;
        $transaction['state'] = self::STATE_CAPTURED;
        unset($transaction['view_data']);

        $transaction_data = $this->saveTransaction($transaction) + $transaction_data;
        $this->execAppCallback(self::CALLBACK_PAYMENT, $transaction_data);

        if ($do_fiscalization) {
            $this->getAdapter()->declareFiscalization($order_id, $this);
        }

        return $transaction_data;
    }

    /**
     * Notifies application about a completed refund.
     */
    protected function handleRefund($order_id, $api_order_refund_amount, $currency, $is_full_refund)
    {
        $transactions = (new waTransactionModel())->getByFields([
            'plugin' => $this->id,
            'app_id' => $this->app_id,
            'merchant_id' => $this->merchant_id,
            'order_id' => $order_id,
            'type' => '',
        ]);
        $initial_transaction = reset($transactions);

        $transaction = $transaction_data = [
            'type' => self::OPERATION_REFUND,
            'state' => $is_full_refund ? self::STATE_REFUNDED : self::STATE_PARTIAL_REFUNDED,
        ] + $this->makeWaTransactionRow($order_id, $api_order_refund_amount, $currency);
        if ($initial_transaction) {
            $transaction['parent_id'] = $initial_transaction['id'];
            $transaction['customer_id'] = $initial_transaction['customer_id'];
        }

        $transaction_data = $this->saveTransaction($transaction) + $transaction_data;
        if ($is_full_refund) {
            // full refund will change order state to 'refund'
            $this->execAppCallback(self::CALLBACK_REFUND, $transaction_data);
        } else {
            // partial refund will only write to order log
            $this->execAppCallback(self::CALLBACK_NOTIFY, $transaction_data);
        }
        return $transaction_data;
    }

    /**
     * Called first when a callback from Webasyst Pay servers is received.
     * This method must determine which app and order the callback is related to.
     * Plugin settings are not available yet. This is why main work is done in callbackHandler().
     *
     * @param array $request
     * @return waPayment
     * @throws waException
     */
    public function callbackInit($request)
    {
        self::log($this->id, [
            'method' => __METHOD__,
            'request_url' => wa()->getConfig()->getRequestUrl(),
            'method' => waRequest::getMethod(),
            'GET' => waRequest::get(),
            'POST' => waRequest::post(),
            'request_body' => file_get_contents("php://input"),
        ]);

        $this->app_id = ifset($request, 'app_id', null);
        $this->merchant_id = ifset($request, 'merchant_id', null);
        return parent::callbackInit($request);
    }

    public function callbackHandler($request)
    {
        $callback_response = [
            'code' => 204,
            'template' => 'string:',
        ];
        $order_id = ifset($request, 'order_id', null);
        if (!$order_id) {
            return $callback_response;
        }

        if (!$this->isCallbackSignatureCorrect($request)) {
            return $callback_response;
        }

        if ($request['state'] == waPayment::STATE_REFUNDED || $request['state'] == waPayment::STATE_PARTIAL_REFUNDED) {
            $this->handleRefund($order_id, $request['amount'], $request['currency_id'], $request['state'] == waPayment::STATE_REFUNDED);
        } else if ($request['state'] == waPayment::STATE_AUTH || $request['state'] == waPayment::STATE_CAPTURED) {
            $this->handlePayment($order_id, $request['amount'], $request['currency_id'], !empty($request['do_fiscalization']));
        }

        return $callback_response;
    }

    protected function isCallbackSignatureCorrect($data)
    {
        $callback_secret = $this->getSettings('callback_secret');
        if (!$callback_secret) {
            self::log($this->id, [
                'Empty callback secret, unable to check signature. Accepting insecure callback data',
                'request' => $data,
            ]);
            return true;
        }
        if (empty($data['signature'])) {
            self::log($this->id, [
                'Ignoring callback without a signature',
                'request' => $data,
            ]);
            return false;
        }
        $signature = hash('sha256', implode('', [
                $data['order_id'],
                $data['type'],
                $data['amount'],
                $data['currency_id'],
                $data['state'],
                $callback_secret,
        ]));
        if ($signature !== $data['signature']) {
            self::log($this->id, [
                'Ignoring callback with incorrect signature',
                'request' => $data,
                'calculated_signature' => $signature,
            ]);
        }
        return true;
    }

    protected function makeWaTransactionRow($order_id, $amount, $currency)
    {
        $transaction_data = [
            'order_id' => $order_id,
            'native_id' => $order_id,
            'amount' => $amount,
            'currency_id' => $currency,
        ] + parent::formalizeData([]);
        return $transaction_data;
    }
}
