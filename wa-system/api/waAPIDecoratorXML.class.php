<?php


class waAPIDecoratorXML extends waAPIDecorator
{

    /**
     * @var DOMDocument
     */
    protected $xml;

    public function decorate($response)
    {
        // создаем xml-документ
        $this->xml = new DOMDocument('1.0', 'UTF-8');
        $this->xml->formatOutput = true;

        // создаем root-элемент
        $root = $this->xml->createElement('response');

        $this->xml->appendChild($root);

        if (!is_array($response)) {
            $root->appendChild($this->xml->createTextNode($response));
        } else {
            $this->parseArray($root, $response);
        }
        return $this->xml->saveXML();
    }

    /**
     * @param DOMElement $context
     * @param array $array
     * @param string $list_item_name
     */
    protected function parseArray(& $context, $array, $list_item_name = null)
    {
        if (!$list_item_name && isset($array['_element'])) {
            $list_item_name = $array['_element'];
            unset($array['_element']);
        }
        foreach ($array as $key => $value) {
            if (!is_int($key) && !is_array($value)) {
                $this->createNode($context, $key, $value);
            } elseif (!is_int($key) && is_array($value)) {
                $with_keys = false;
                $n = count($value);
                for ($i = 0, reset($value); $i < $n; $i++, next($value)) {
                    if (key($value) !== $i) {
                        $with_keys = true;
                        break;
                    }
                }
                $first_element = reset($value);
                $sub_key = null;
                if (is_array($first_element) && substr($key, -1) == 's') {
                    if (substr($key, -3) == 'ies') {
                        $sub_key = substr($key, 0, -3).'y';
                    } else {
                        $sub_key = substr($key, 0, -1);
                    }
                }
                if ($with_keys || $sub_key) {
                    $element = $this->xml->createElement($key);
                    $context->appendChild($element);
                    $this->parseArray($element, $value, $sub_key);
                } else {
                    $this->parseArray($context, $value, $key);
                }
            } elseif (is_int($key) && is_array($value)) {
                if ($list_item_name) {
                    $element = $this->xml->createElement($list_item_name);
                    $context->appendChild($element);
                    $this->parseArray($element, $value);
                } else {
                    $this->parseArray($context, $value);
                }
            } elseif (is_int($key) && !is_array($value)) {
                if ($list_item_name) {
                    $this->createNode($context, $list_item_name, $value);
                }
            }
        }
    }

    /**
     * @param DOMElement$context
     * @param string $name
     * @param string $value
     */
    protected function createNode(& $context, $name, $value)
    {
        $element = $this->xml->createElement((string)$name);
        if ((string)$value !== '') {
            $element->appendChild($this->xml->createTextNode((string)$value));
        }
        $context->appendChild($element);
    }
}
