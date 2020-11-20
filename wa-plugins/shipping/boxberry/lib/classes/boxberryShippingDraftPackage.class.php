<?php

/**
 * Class boxberryShippingDraftPackage
 */
class boxberryShippingDraftPackage
{
    /**
     * @var boxberryShipping|null
     */
    protected $bxb = null;

    /**
     * @var waOrder
     */
    protected $order;

    /**
     * @var array
     */
    protected $shipping_data;

    /**
     * boxberryShippingDraftPackage constructor.
     * @param boxberryShipping $bxb
     * @param waOrder $order
     * @param array $shipping_data
     */
    public function __construct(boxberryShipping $bxb, waOrder $order, $shipping_data = [])
    {
        $this->bxb = $bxb;
        $this->order = $order;
        $this->shipping_data = $shipping_data;
    }

    /**
     * @return array
     */
    public function createDraft()
    {
        $data = [
            'order_id'     => $this->getOrderId(),
            'delivery_sum' => $this->order->shipping,
            'payment_sum'  => $this->getPaysum(),
            'vid'          => $this->getDeliveryType(),
            'items'        => $this->getItems(),
            'weights'      => [
                'weight' => $this->bxb->getParcelWeight(),
            ],
            'issue'        => $this->bxb->issuance,
            'sender_name'  => $this->bxb->notification_name
        ];

        $data['weights'] += $this->getParcelVolume();

        // If the number is saved, then you need to update
        if (!empty($this->order->shipping_data['original_track_number'])) {
            $data['updateByTrack'] = $this->order->shipping_data['original_track_number'];
        }

        $declared_price = $this->bxb->declared_price;
        if ($declared_price) {
            $data['price'] = $this->getDeclaredPrice();
        }

        if ($this->isCourierShipping()) {
            $data = $this->extendByCourier($data);
        }

        $data['shop'] = [
            'name'  => $this->getPointCode(),
            'name1' => $this->bxb->targetstart
        ];

        $contact_info = [
            'fio' => $this->order->getContactField('name')
        ];

        $contact_phone = $this->order->getContactField('phone');
        if ($contact_phone) {
            $contact_info['phone'] = $contact_phone;
        }

        $contact_email = $this->order->getContactField('email');
        if ($contact_email) {
            $contact_info['email'] = $contact_email;
        }

        $data['customer'] = $contact_info;

        return $this->sendCreateDraftRequest($data);
    }


    /**
     * @return string
     */
    protected function getPointCode()
    {
        $result = '';

        if ($this->isPointShipping()) {
            $rate_id = $this->order->shipping_rate_id;
            $rate_data = explode(boxberryShippingCalculatePoints::getVariantSeparator(), $rate_id);

            $result = end($rate_data);
        }
        return $result;
    }

    /**
     * Adds required fields for the courier
     *
     * @param $data
     * @return array
     */
    protected function extendByCourier($data)
    {
        $shipping_address = $this->order->shipping_address;

        $data['kurdost'] = [
            'index'    => ifset($shipping_address, 'zip', ''),
            'addressp' => ifset($shipping_address, 'street', ''),
            'comentk'  => $this->order->comment,
        ];

        $city = ifset($shipping_address, 'city', '');
        $region = ifset($shipping_address, 'region_name', '');

        $data['kurdost']['citi'] = $region.', '.$city;

        return $data;
    }

    /**
     * @return bool|int
     */
    protected function getDeliveryType()
    {
        $type = false;
        if ($this->isPointShipping()) {
            $type = 1;
        } elseif ($this->isCourierShipping()) {
            $type = 2;
        }

        return $type;
    }

    /**
     * @return bool
     */
    protected function isCourierShipping()
    {
        $result = false;
        $rate_id = $this->order->shipping_rate_id;

        if (strpos($rate_id, boxberryShippingCalculateCourier::VARIANT_PREFIX) !== false) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function isPointShipping()
    {
        $result = false;
        $rate_id = $this->order->shipping_rate_id;

        if (strpos($rate_id, boxberryShippingCalculatePoints::VARIANT_PREFIX) !== false) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getItems()
    {
        $result = [];
        $items = $this->order->items;

        foreach ($items as $item) {
            $bxb_item = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'price'    => round($item['price'] - $item['discount'], 2),
                'quantity' => $item['quantity'],
                'UnitName' => 'шт.'
            ];

            if (is_numeric($item['tax_rate'])) {
                $bxb_item['nds'] = (float) $item['tax_rate'];
            }

            $result[] = $bxb_item;
        }
        return $result;
    }

    protected function getParcelVolume()
    {
        $item = current(ref($this->order->items));
        $dimensions = array('x' => 'width', 'y' => 'height', 'z' => 'length');
        foreach ($dimensions as $key => $dimension) {
            if (
                isset($item[$dimension])
                && !empty($item[$dimension])
                && $item['dimensions_unit']
                && $item[$dimension] < floatval($this->bxb->getSettings('max_' . $dimension))
            ) {
                $dimensions[$key] = ceil(shopDimension::getInstance()->convert($item[$dimension], 'length', 'cm', $item['dimensions_unit']));
            } else {
                return array();
            }
        }
        return $dimensions;
    }

    /**
     * Validates the request and sends a request to create a draft
     *
     * @param $data
     * @return array
     */
    protected function sendCreateDraftRequest($data)
    {
        $error = $this->validateRequest($data);
        $result = [];

        if (!$error) {
            $api_manager = new boxberryShippingApiManager($this->bxb->token, $this->bxb->api_url, $this->bxb);

            $sdata = array();
            foreach ($data as $key => $value) {
                $sdata[$key] = $value;
                if ($key === 'order_id') {
                    // partner_token goes right after 'order_id' key
                    $sdata['partner_token'] = 'Webasyst001';
                }
            }

            $request = ['sdata' => json_encode($sdata)];
            $send = $api_manager->createDraft($request);

            if (!empty($send['track'])) {
                $result = [
                    'original_track_number' => $send['track'],
                    'tracking_number'       => $send['track'],
                    'view_data'             => $this->getViewData($send),
                ];
            }

            if ($api_manager->getErrors()) {
                $result['view_data'] = $this->bxb->_w('Error during automatic shipment creation. Please create a shipment manually in your personal account on Boxberry website. See detailed error-related information in log file <em>wa-log/wa-plugins/shipping/boxberry/api_requests.log</em>.');
            }
        }

        return $result;
    }

    /**
     * @param array $request
     * @return string
     */
    protected function getViewData($request)
    {
        $url = 'https://account.boxberry.ru/parcel/info?parcel_id=%s';

        $track = $request['track'];

        $template = "Создан заказ в личном кабинете Boxberry <a href='{$url}' target='_blank'>№%s<i class='icon16 new-window'></i></a>";
        if (!empty($this->order->shipping_data['original_track_number'])) {
            $template = "Обновлен заказ в личном кабинете Boxberry <a href='{$url}' target='_blank'>№%s.<i class='icon16 new-window'></i></a>";
        }

        $template = str_replace('%s', $track, $template);

        $label = ifset($request, 'label', false);
        if ($label) {
            $template .= "<a href='{$label}' target='_blank'>Этикетка<i class='icon16 new-window'></i></a> ";
        }
        return $template;
    }

    /**
     * Checks the final request for all required fields
     *
     * @param $data
     * @return bool
     */
    protected function validateRequest($data)
    {
        $error = false;

        if (empty($data['order_id']) || empty($data['vid']) || empty($data['shop']['name1'])) {
            $error = true;
        }

        if ($this->isCourierShipping() && (empty($data['kurdost']['citi']) || empty($data['kurdost']['addressp']))) {
            $error = true;
        }

        if ($this->isPointShipping() && empty($data['shop']['name'])) {
            $error = true;
        }

        if (empty($data['customer']['fio']) || empty($data['customer']['phone'])) {
            $error = true;
        }

        if (ifset($data, 'weights', 'weight', 0) <= 0) {
            $error = true;
        }

        return $error;
    }

    /**
     * Valid character set: a-z(A-Z), 0-9, а-я(А-Я), ёЁ, dash(-), forward slash(/), dot(.), comma(,), underscore(_), №, space
     * Max length: 35 symbols
     *
     * @return string
     */
    protected function getOrderId()
    {
        $order_id = $this->order->id_str;

        /** @noinspection RegExpRedundantEscape */
        $result = preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ\-.,_\/№ ]/u', '', $order_id);

        if (!$result) {
            $result = $this->order->id;
        }

        $result = substr($result, 0, 35);

        return $result;
    }

    /**
     * @return float|int
     */
    protected function getPaysum()
    {
        $paysum = $this->order->total;
        $is_courier_prepayment = $this->isCourierShipping() && $this->bxb->courier_mode === 'prepayment';
        $is_point_prepayment = $this->isPointShipping() && $this->bxb->point_mode === 'prepayment';

        if ($is_courier_prepayment || $is_point_prepayment || $this->order->paid_datetime) {
            $paysum = 0;
        }

        $payment_type = $this->bxb->getSelectedPaymentTypes();
        if ($payment_type && in_array(waShipping::PAYMENT_TYPE_PREPAID, $payment_type)) {
            $paysum = 0;
        }

        return $paysum;
    }

    /**
     * @return float|int
     */
    protected function getDeclaredPrice()
    {
        $helper = new boxberryShippingCalculateHelper($this->bxb);

        // Because you need to transfer only the value of goods in declared value.
        $paysum = $this->getPaysum();
        if ($paysum > 0) {
            $paysum = $this->order->total - $this->order->shipping;
        }

        return $helper->getOrderSum($paysum);
    }
}
