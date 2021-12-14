<?php

/**
 * @property string $id
 * @property string $id_str
 *
 * @property int $contact_id
 *
 * @property string $currency ISO3 code
 * @property double $total
 * @property double $tax
 * @property double $discount
 *
 * @property double $subtotal
 * @property double $shipping shipping price
 * @property string $shipping_name
 * @property string $payment_name
 * @property bool $tax_included
 *
 * @property bool $recurrent
 * @property bool $save_card
 *
 * @property string description
 * @property string description_en
 *
 * @property string $datetime
 * @property string $update_datetime
 * @property string $paid_datetime
 *
 * @property mixed[] $shipping_params
 * @property string[] $shipping_data
 * @property string $shipping_rate_id
 * @property string $shipping_plugin Plugin string id usps,dhl
 * @property float $shipping_tax_rate
 * @property float $shipping_tax_included
 *
 *
 * @property array $shipping_address
 * @property array[string]string $shipping_address['name']
 * @property array[string]string $shipping_address['firstname']
 * @property array[string]string $shipping_address['lastname']
 * @property array[string]string $shipping_address['zip']
 * @property array[string]string $shipping_address['street']
 * @property array[string]string $shipping_address['city']
 * @property array[string]string $shipping_address['region']
 * @property array[string]string $shipping_address['region_name']
 * @property array[string]string $shipping_address['country']
 * @property array[string]string $shipping_address['country_name']
 * @property array[string]string $shipping_address['address']
 *
 * @property mixed[] $billing_params
 * @property string[] $billing_data
 *
 * @property array $billing_address
 * @property array[string]string $billing_address['name']
 * @property array[string]string $billing_address['firstname']
 * @property array[string]string $billing_address['lastname']
 * @property array[string]string $billing_address['address']
 * @property array[string]string $billing_address['zip']
 * @property array[string]string $billing_address['street']
 * @property array[string]string $billing_address['city']
 * @property array[string]string $billing_address['region']
 * @property array[string]string $billing_address['region_name']
 * @property array[string]string $billing_address['country']
 * @property array[string]string $billing_address['country_name']
 * @property array[string]string $billing_address['address']
 *
 * @property array $items
 * @property array[][string]string  $items[]['id']
 * @property array[][string]string  $items[]['name']
 * @property array[][string]string  $items[]['description']
 * @property array[][string]string  $items[]['img']
 * @property array[][string]double  $items[]['price']
 * @property array[][string]double  $items[]['height']
 * @property array[][string]double  $items[]['width']
 * @property array[][string]double  $items[]['length']
 * @property array[][string]double  $items[]['weight']
 * @property array[][string]int     $items[]['quantity']
 * @property array[][string]double  $items[]['discount']
 * @property array[][string]double  $items[]['total_discount']
 * @property array[][string]double  $items[]['total']
 * @property array[][string]double  $items[]['tax_rate'] Tax rate in percent
 * @property array[][string]boolean $items[]['tax_included'] Tax is included into price
 * @property array[][int][]         $items[]['product_codes'] - product code array <id:int> => <product_code_record:array>
 *      Product code item format:
 *          int      'id'
 *          string   'code'
 *          string   'name' [optional]
 *          string   'icon' [optional]
 *          string   'logo' [optional]
 *          string[] 'values' - one or more values, possibly for each instance of product item if provided
 *
 *
 *
 * @property int $total_quantity
 *
 * @property mixed $contact_%field%
 * @property-read string $contact_email
 * @property-read string $contact_name
 *
 * @property string $comment
 * @property array $params
 * @property array[string]mixed $params[]
 * @property string $card_native_id
 *
 *
 */
class waOrder implements ArrayAccess
{
    private $fields = array();
    private $data = array();
    /**
     *
     * customer
     * @var waContact
     */
    private $contact;
    private $alias;

    /**
     * @param array|waOrder $data
     * @return waOrder
     */
    public static function factory($data = array())
    {
        if ($data instanceof self) {
            return $data;
        } else {
            return new self($data);
        }
    }

    public function __construct($data = array())
    {
        $this->data['billing_params'] = array();
        $this->data['billing_data'] = array();

        $this->data['shipping_params'] = array();
        $this->data['shipping_data'] = array();

        $this->alias = array(
            'order_id'            => 'id',
            'order_id_str'        => 'id_str',
            'customer_contact_id' => 'contact_id',
            'customer_id'         => 'contact_id',
            'amount'              => 'total',
            'currency_id'         => 'currency',
            'create_datetime'     => 'datetime',
        );
        if (!empty($data)) {
            foreach ($data as $field => $value) {
                switch ($field) {
                    case 'contact':
                        if ($value instanceof waContact) {
                            $this->contact = $value;
                            $field = 'contact_id';
                            $value = $this->contact->getId();
                        }
                        break;
                    case 'params':
                        foreach ($value as $sub_field => $sub_value) {
                            if (preg_match('@^(shipping|payment|billing)_(params|data)_(.+)$@', $sub_field, $matches)) {
                                switch ($matches[1]) {
                                    case 'shipping':
                                        $this->data['shipping_'.$matches[2]][$matches[3]] = $sub_value;
                                        break;
                                    case 'payment':
                                    case 'billing':
                                        $this->data['billing_'.$matches[2]][$matches[3]] = $sub_value;
                                        break;
                                }
                            } elseif (preg_match('@^shipping_(rate_id|plugin|name)$@', $sub_field, $matches)) {
                                $this->data[$sub_field] = $sub_value;
                            }
                        }
                        break;
                    case 'items':
                        foreach ($value as &$item) {
                            $item += array(
                                'id'          => null,
                                'name'        => null,
                                'description' => null,
                                'img'         => null,
                                'price'       => null,
                                'height'      => null,
                                'width'       => null,
                                'length'      => null,
                                'weight'      => null,
                                'quantity'    => 1,
                                'product_codes' => [],
                            );
                            $item += array(
                                'total' => $item['price'] * $item['quantity'],
                            );
                            unset($item);
                        }

                        break;
                }

                $this->data[$field] = $value;
                $this->fields[] = $field;
                if (isset($this->alias[$field]) && !isset($data[$this->alias[$field]])) {
                    $this->data[$this->alias[$field]] = &$this->data[$field];
                    $this->fields[] = $this->alias[$field];
                }
            }
        }

        $subtotal = 0.0 + $this->total;
        if ($this->tax_included !== true) {
            $subtotal -= $this->tax;
        }

        $subtotal += $this->discount;
        $subtotal -= $this->shipping;

        $this->subtotal = $subtotal;
        $this->init();
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        return $this->offsetSet($name, $value);
    }

    /**
     * var_dump handler
     * @return array
     */
    public function __debugInfo()
    {
        return array(
            'contact_id'      => $this->contact_id,
            'id'              => $this->id,
            'currency'        => $this->currency,
            'total'           => $this->total,
            'shipping'        => $this->shipping,
            'subtotal'        => $this->subtotal,
            'discount'        => $this->discount,
            'items'           => $this->items,
            'shipping_params' => $this->shipping_params,
            'shipping_data'   => $this->shipping_data,
            'billing_params'  => $this->billing_params,
            'billing_data'    => $this->billing_data,
            'data'            => $this->data,
        );
    }

    /**
     * @param array $an_array
     * @return waOrder
     */
    public static function __set_state($an_array)
    {
        $data = array();
        if (is_array($an_array) && isset($an_array['data'])) {
            $data = $an_array['data'];
        }

        return new self($data);
    }

    /**
     * @param mixed $offset
     * @return bool
     * @internal param $offset
     */
    public function offsetExists($offset)
    {
        if (isset($this->alias[$offset])) {
            $offset = $this->alias[$offset];
        }
        return isset($this->data[$offset]);

    }

    /**
     * @param $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (isset($this->alias[$offset])) {
            $offset = $this->alias[$offset];
        }
        if (!isset($this->data[$offset])) {
            if ($method = $this->methodName($offset)) {
                if (!in_array($offset, $this->fields)) {
                    $this->fields[] = $offset;
                }
                return $this->data[$offset] = $this->$method();
            } elseif (preg_match('/^contact_(.+)$/', $offset, $matches)) {
                return $this->data[$offset] = $this->getContactField($matches[1]);
            }
            return $this->data[$offset] = null;
        } else {
            return isset($this->data[$offset]) ? $this->data[$offset] : null;
        }
    }

    private function methodName($offset)
    {
        $method = 'get'.preg_replace_callback('/(^|_)([\w])/', wa_lambda('$c', 'return strtoupper($c[2]);'), $offset);
        return method_exists($this, $method) ? $method : null;
    }

    /**
     * @param $offset
     * @param $value
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        if (isset($this->alias[$offset])) {
            $offset = $this->alias[$offset];
        }
        if (!in_array($offset, $this->fields)) {
            $this->fields[] = $offset;
        }
        return $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

    private function init()
    {
        $this->initAddress($this->data['billing_address']);
        $this->initAddress($this->data['shipping_address']);
        if (empty($this->data['description_en'])) {
            $this->data['description_en'] = waLocale::transliterate(ifset($this->data['description'], ''));
        }
    }

    private function initAddress(&$address)
    {
        static $model;
        $dummy_address = array_fill_keys(array(
            'name',
            'firstname',
            'lastname',
            'address',
            'zip',
            'street',
            'city',
            'region',
            'country',
            'address',
        ), '');
        if (is_array($address)) {
            $address = array_merge($dummy_address, $address);
        } else {
            $address = $dummy_address;
        }
        if (empty($address['country_name'])) {
            $address['country_name'] = waCountryModel::getInstance()->name(ifempty($address['country']));
        }
        if (empty($address['region_name'])) {

            $address['region_name'] = '';
            if (!empty($address['country']) && !empty($address['region'])) {
                if (!$model) {
                    $model = new waRegionModel();
                }
                $region = $model->get($address['country'], $address['region']);
                if ($region) {
                    $address['region_name'] = $region['name'];
                }
            }
        }
        if (empty($address['address'])) {
            $fields = array('street', 'city', 'region_name', 'zip', 'country_name',);
            $address['address'] = '';
            $chunks = array();
            foreach ($fields as $field) {
                if (!empty($address[$field])) {
                    $chunks[] = $address[$field];
                }
            }
            $address['address'] = implode(', ', $chunks);

            $address_length = 119;
            $pattern = sprintf('/^(.{1,%d}),\s+(.*?)$/u', $address_length);

            if ((mb_strlen($address['address']) > $address_length) && preg_match($pattern, $address['address'], $matches)) {
                $address['address_1'] = $matches[1];
                $address['address_2'] = $matches[2];
            } else {
                $address['address_1'] = $address['address'];
                $address['address_2'] = '';
            }

            $address['name'] = '';
            $fields = array('firstname', 'lastname', 'middlename',);
            foreach ($fields as $field) {
                if (!empty($address[$field])) {
                    $address['name'] .= ' '.$address[$field];
                }
            }

            $address['name'] = trim($address['name']);
        }
    }

    /**
     *
     * @return waContact|null
     */
    public function getContact()
    {
        if (!empty($this->data['contact_id'])) {
            if (!$this->contact) {
                $this->contact = new waContact($this->contact_id);
            }
            return $this->contact;
        } elseif ($this->contact instanceof waContact) {
            return $this->contact;
        }
        return null;
    }

    public function getTotalQuantity()
    {
        $items = ifempty($this->data['items'], array());
        $quantity = 0;
        foreach ($items as $item) {
            $quantity += $item['quantity'];
        }
        return $quantity;
    }

    public function getTotalDiscount()
    {
        $items = ifempty($this->data['items'], array());
        $discount = 0;
        foreach ($items as $item) {
            $discount += isset($item['total_discount']) ? $item['total_discount'] : $item['discount'] * $item['quantity'];
        }
        return $discount;
    }

    public function getContactField($field, $format = null)
    {
        if ($field === 'contact_id') {
            return ifset($this->data['contact_id']);
        } elseif ($this->getContact()) {
            $value = $this->contact->get($field, $format);
            if (is_array($value)) {
                $res = reset($value);
                if (is_array($res)) {
                    $value = $res['value'];
                } else {
                    $value = $res;
                }
            }
            return $value;
        } else {
            return null;
        }
    }
}
