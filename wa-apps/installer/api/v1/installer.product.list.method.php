<?php

class installerProductListMethod extends waAPIMethod
{
    protected $method = 'GET';

    public function execute()
    {
        $slugs = $this->getSlugs();
        $fields = $this->getFields();

        $products = installerHelper::getStoreProductsData($slugs, array_keys($fields));

        foreach($products as $k => $p) {
            if (!isset($fields['requirements'])) {
                unset($products[$k]['requirements']);
            }
            if (!isset($fields['tags'])) {
                unset($products[$k]['tags']);
            }
        }

        $this->response = [
            'products' => $products,
        ];
    }

    protected function getSlugs()
    {
        $slugs = $this->get('slugs', true);
        return array_map('trim', explode(',', $slugs));
    }

    protected function getFields()
    {
        $fields = [
            'name' => 1,
            // Implemented: price,icon,tags,requirements
            // Not implemented but may be useful: is_stopsale,is_installed
        ];
        foreach(explode(',', (string) $this->get('fields')) as $f) {
            $f = trim((string)$f);
            if ($f) {
                $fields[$f] = 1;
            }
        }

        return $fields;
    }
}
