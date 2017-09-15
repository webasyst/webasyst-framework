<?php

/**
 *
 * @author      Webasyst
 * @name                  Paymaster
 * @description Paymaster payment module
 *
 * @see         http://info.paymaster.ru/api/
 */
class paymasterPayment extends waPayment implements waIPayment
{

	//Переадресация на систему оплаты PayMaster

	protected $endpointUrl = 'https://paymaster.ru/Payment/Init';

	protected $currency = array('RUB', 'UAH', 'USD', 'EUR', 'UZS', 'BYR');

	/**
	 * Возвращаем допустимые валюты
	 * @return array
	 */
	public function allowedCurrency()
	{
		return $this->currency;
	}

	public function payment($payment_form_data, $order_data, $auto_submit = false)
	{
		// заполняем обязательный элемент данных с описанием заказа
		if (empty($order_data['description']))
		{
			$order_data['description'] = $this->description . $order_data['order_id'];
		}
		// вызываем класс-обертку, чтобы гарантировать использование данных в правильном формате
		$order = $order_data = waOrder::factory($order_data);

		// добавляем в платежную форму поля, требуемые платежной системой WebMoney
		$hidden_fields = array(
			'LMI_MERCHANT_ID'    => $this->LMI_MERCHANT_ID,
			'LMI_PAYMENT_AMOUNT' => number_format($this->getOrderAmount($order), 2, '.', ''),
			'LMI_CURRENCY'       => strtoupper($order->currency),
			'LMI_PAYMENT_NO'     => $order_data['order_id'],
			'LMI_PAYMENT_DESC'   => $order->description,
			'wa_app'             => $this->app_id,
			'wa_merchant_contact_id' => $this->merchant_id,
			'SIGN'               => $this->getSign($this->LMI_MERCHANT_ID, $order_data['order_id'], number_format($this->getOrderAmount($order), 2, '.', ''), strtoupper($order->currency), $this->secretPhrase, $this->signMethod), 'LMI_PAYMENT_NOTIFICATION_URL' => $this->getRelayUrl(),
		);

		if ($this->testMode)
		{
			$hidden_fields['LMI_SIM_MODE'] = $this->testMode;
		}
		if (!empty($order_data['customer_info']['email']))
		{
			$hidden_fields['LMI_PAYER_EMAIL'] = $order_data['customer_info']['email'];
		}


		$transaction_data = $this->formalizeData($hidden_fields);
		// добавляем служебные URL:
		// URL возврата покупателя после успешного завершения оплаты
		$hidden_fields['LMI_SUCCESS_URL'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
		// URL возврата покупателя после неудачной оплаты
		$hidden_fields['LMI_FAILURE_URL'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

		foreach ($order->items as $key => $product)
		{
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].NAME"]  = $product['name'];
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].QTY"]   = $product['quantity'];
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = number_format($product['price'] - $product['discount'], 2, '.', '');

			if ($this->vatProducts == 'map')
			{
				$this->vatProducts = 'no_vat';
			}
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].TAX"] = $this->vatProducts;
		}

		if ($order->shipping > 0)
		{
			$key++;
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].NAME"]  = $order->shipping_name;
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].QTY"]   = 1;
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].PRICE"] = number_format($order->shipping, 2, '.', '');
			$hidden_fields["LMI_SHOPPINGCART.ITEM[{$key}].TAX"]   = $this->vatDelivery;
		}

		$view = wa()->getView();
		$view->assign('url', wa()->getRootUrl());
		$view->assign('hidden_fields', $hidden_fields);
		$view->assign('form_url', $this->getEndpointUrl());
		$view->assign('form_options', $this->getFormOptions());
		$view->assign('auto_submit', $auto_submit);
		$view->assign('order', $order);

		// для отображения платежной формы используем собственный шаблон
		return $view->fetch($this->path . '/templates/payment.html');
	}

	/*
	 * Какая то непонятная функция связанная с webmoney
	 */
	private function getFormOptions()
	{
		$options = array();

		$options['accept-charset'] = 'windows-1251';

		return $options;
	}




	/**
	 * @param array $request
	 *
	 * @return waPayment
	 */
	protected function callbackInit($request)
	{
		if (!empty($request['LMI_PAYMENT_NO']) && !empty($request['wa_app']) && !empty($request['LMI_MERCHANT_ID']))
		{
			$this->order_id    = $request['LMI_PAYMENT_NO'];
			$this->app_id      = $request['wa_app'];
			$this->merchant_id = $request['wa_merchant_contact_id'];
		}
		else
		{
			self::log($this->id, array('error' => 'empty required field(s)'));
			throw new waPaymentException('Empty required field(s)');
		}

		return parent::callbackInit($request);
	}

	/**
	 *
	 * @param array $request - get from gateway
	 *
	 * @throws waPaymentException
	 * @return mixed
	 */
	protected function callbackHandler($request)
	{

		$transaction_data = $this->formalizeData($request);

		$transaction_data['order_id'] = $request['LMI_PAYMENT_NO'];

		//Получаем сразу HASH
		$hash = $this->getHash($request['LMI_MERCHANT_ID'], $request['LMI_PAYMENT_NO'], $request['LMI_SYS_PAYMENT_ID'], $request['LMI_SYS_PAYMENT_DATE'], $request['LMI_PAYMENT_AMOUNT'], $request['LMI_CURRENCY'], $request['LMI_PAID_AMOUNT'], $request['LMI_PAID_CURRENCY'], $request['LMI_PAYMENT_SYSTEM'], $request['LMI_SIM_MODE'], $this->secretPhrase, $this->signMethod);

		//Получаем сразу подпись
		$sign = $this->getSign($request['LMI_MERCHANT_ID'], $request['LMI_PAYMENT_NO'], $request['LMI_PAID_AMOUNT'], $request['LMI_PAID_CURRENCY'], $this->secretPhrase, $this->signMethod);


		if ($_SERVER["REQUEST_METHOD"] == "POST")
		{
			self::log($this->id, array('success' => 'Начало тестирования оплаты'));

			$order_total = $request['LMI_PAID_AMOUNT'];

			if ($request['LMI_PREREQUEST'])
			{
				if (($request['LMI_MERCHANT_ID'] == $this->LMI_MERCHANT_ID) && ($request['LMI_PAYMENT_AMOUNT'] == $order_total))
				{
					self::log($this->id, array('success' => 'Test finished with success'));
					echo 'YES';
					exit;
				}
				else
				{
					self::log($this->id, array('error' => 'Test finished with error'));
					echo 'FAIL';
					exit;
				}
			}
			else
			{

				if ($request['LMI_HASH'] == $hash)
				{

					if ($sign == $request['SIGN'])
					{
						$transaction_data   = $this->saveTransaction($transaction_data, $request);
						$app_payment_method = self::CALLBACK_PAYMENT;
						$result             = $this->execAppCallback($app_payment_method, $transaction_data);
						self::log($this->id, array('success' => 'Payment paymaster finished with success'));

						return $result;
					}
					else
					{
						self::log($this->id, array('error' => 'Invalid SIGN'));

						return;
					}
				}
				else
				{
					self::log($this->id, array('error' => 'Invalid HASH'));

					return;
				}
			}
		}

		return;
	}


	/**
	 * Устанавливаем статус заказа
	 * @param $statusID
	 */
	public function setOrderStatus($statusID)
	{

	}

	/**
	 * Для вызова интерфейса PayMaster
	 * @return string
	 */
	private function getEndpointUrl()
	{

		return $this->endpointUrl;
	}

	/**
	 * Получение суммы заказа
	 * Для чего нужна эта функция спросите вы?! 
	 * Если в Shop Script включены налоги НДС, есть вероятность, что они будут включаться сверх стоимости товара
	 * в сумме заказа, например, товара у вас на 5000 рублей (1 товар по цене 5000 рублей) НДС 18 = 900 рублей 
	 * сумма заказа будет на 5000 рублей, как на странице товара, а 5900 рублей. То есть НДС идет плюсом. Его просто потом сложно 
	 * высчитать будет  
	 * @return float
	 */
	private function getOrderAmount($order)
	{
		$amount = 0;

		foreach ($order->items as $key => $product)
		{
			$amount += ($product['price'] - $product['discount']) * $product['quantity'];
		}

		if ($order->shipping > 0)
		{
			$amount += $order->shipping;
		}

		return $amount;
	}


	/**
	 * Convert transaction raw data to formatted data
	 *
	 * @param array $transaction_raw_data
	 *
	 * @return array $transaction_data
	 * @throws waPaymentException
	 */
	protected function formalizeData($transaction_raw_data)
	{
		$transaction_data = parent::formalizeData($transaction_raw_data);

		return $transaction_data;
	}

	/**
	 * Возвращаем HASH запроса
	 *
	 * @param        $LMI_MERCHANT_ID
	 * @param        $order_id
	 * @param        $amount
	 * @param        $lmi_currency
	 * @param        $secret_key
	 * @param string $sign_method
	 *
	 * @return string
	 */
	public function getHash($LMI_MERCHANT_ID, $LMI_PAYMENT_NO, $LMI_SYS_PAYMENT_ID, $LMI_SYS_PAYMENT_DATE, $LMI_PAYMENT_AMOUNT, $LMI_CURRENCY, $LMI_PAID_AMOUNT, $LMI_PAID_CURRENCY, $LMI_PAYMENT_SYSTEM, $LMI_SIM_MODE, $SECRET, $hash_method = 'md5')
	{
		$string = $LMI_MERCHANT_ID . ";" . $LMI_PAYMENT_NO . ";" . $LMI_SYS_PAYMENT_ID . ";" . $LMI_SYS_PAYMENT_DATE . ";" . $LMI_PAYMENT_AMOUNT . ";" . $LMI_CURRENCY . ";" . $LMI_PAID_AMOUNT . ";" . $LMI_PAID_CURRENCY . ";" . $LMI_PAYMENT_SYSTEM . ";" . $LMI_SIM_MODE . ";" . $SECRET;

		$hash = base64_encode(hash($hash_method, $string, true));

		return $hash;
	}


	/**
	 * Возвращаем подпись
	 *
	 * @param        $LMI_MERCHANT_ID
	 * @param        $order_id
	 * @param        $amount
	 * @param        $lmi_currency
	 * @param        $secret_key
	 * @param string $sign_method
	 *
	 * @return string
	 */
	public function getSign($merchant_id, $order_id, $amount, $lmi_currency, $secret_key, $sign_method = 'md5')
	{

		$plain_sign = $merchant_id . $order_id . $amount . $lmi_currency . $secret_key;
		$sign       = base64_encode(hash($sign_method, $plain_sign, true));

		return $sign;
	}


	/**
	 * Возвращаем список опций для продуктов
	 **/
	public function vatProductsOptions()
	{
		$disabled = !$this->getAdapter()->getAppProperties('taxes');

		return array(
			array(
				'value' => 'vat18',
				'title' => 'НДС 18%',
			),
			array(
				'value' => 'vat18',
				'title' => 'НДС 10%',
			),
			array(
				'value' => 'vat118',
				'title' => 'НДС по формуле 18/118%',
			),
			array(
				'value' => 'vat110',
				'title' => 'НДС по формуле 10/110%',
			),
			array(
				'value' => 'vat0',
				'title' => 'НДС 0%',
			),
			array(
				'value' => 'no_vat',
				'title' => 'без НДС',
			),
			array(
				'value'    => 'map',
				'title'    => 'Передавать ставки НДС по каждой позиции',
				'disabled' => true, //Пока пришлось отключить данную фичу по той простой причине, что в PayMaster передаются все цены на товары без НДС! 
			),
		);
	}

	/**
	 * Возвращаем ставку НДС для доставки
	 * по сути это просто список с выбором
	 **/
	public function vatDeliveryOptions()
	{
		return array(
			array(
				'value' => 'vat18',
				'title' => 'НДС 18%',
			),
			array(
				'value' => 'vat18',
				'title' => 'НДС 10%',
			),
			array(
				'value' => 'vat118',
				'title' => 'НДС по формуле 18/118%',
			),
			array(
				'value' => 'vat110',
				'title' => 'НДС по формуле 10/110%',
			),
			array(
				'value' => 'vat0',
				'title' => 'НДС 0%',
			),
			array(
				'value' => 'no_vat',
				'title' => 'без НДС',
			),
		);
	}


	/**
	 * Получение налогового кода
	 * Пока функция не задействована - скопирована с Yandex Money 
	 *
	 * @param $item
	 *
	 * @return int
	 */
	private function getTaxId($item)
	{
		$id = 1;
		switch ($this->taxes)
		{
			case 'no':
				# 1 — без НДС;
				$id = 1;
				break;
			case 'map':
				$rate = ifset($item['tax_rate']);
				if (in_array($rate, array(null, false, ''), true))
				{
					$rate = -1;
				}
				switch ($rate)
				{
					case 18: # 4 — НДС чека по ставке 18%;
						$id = 4;
						break;
					case 10: # 3 — НДС чека по ставке 10%;
						$id = 3;
						break;
					case 0: # 2 — НДС по ставке 0%;
						$id = 2;
						break;
					default: # 1 — без НДС;
						$id = 1;
						break;
				}
				break;
		}

		return $id;
	}
}
