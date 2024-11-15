<?php

class siteInstaller {

    public function addDefaultVariables() {
        $model = new waModel();

        if (!$model->query('SELECT COUNT(*) FROM site_variable')->fetchField()) {
            $locale_data = [
                'ru_RU' => [
                    ['id' => 'company-name', 'content' => 'ООО "Моя компания"', 'description' => 'Название компании', 'sort' => 1],
                    ['id' => 'address', 'content' => 'Москва, ул. Пушкина, д. 1, оф. 1', 'description' => 'Адрес', 'sort' => 2],
                    ['id' => 'phone', 'content' => '+7 (123) 456-78-90', 'description' => 'Телефон', 'sort' => 3],
                    ['id' => 'email', 'content' => 'info@your-company.com', 'description' => 'Email', 'sort' => 4],
                ],
                'en_US' => [
                    ['id' => 'company-name', 'content' => 'Your Company Name Ltd', 'description' => 'Company Name', 'sort' => 1],
                    ['id' => 'address', 'content' => '123 Kingsway, London, WC2B 6NH, United Kingdom', 'description' => 'Address', 'sort' => 2],
                    ['id' => 'phone', 'content' => '+44 01 2345 6789', 'description' => 'Phone', 'sort' => 3],
                    ['id' => 'email', 'content' => 'info@your-company.com', 'description' => 'Email', 'sort' => 4],
                ]
            ];

            $data_to_insert = waLocale::getLocale() === 'ru_RU' ? $locale_data['ru_RU'] : $locale_data['en_US'];

            foreach ($data_to_insert as $data) {
                $model->exec('INSERT INTO `site_variable`(`id`, `content`, `create_datetime`, `description`, `sort`) VALUES (?, ?, CURRENT_TIMESTAMP, ?, ?)',
                    $data['id'], $data['content'], $data['description'], $data['sort']);
            }
        }
    }
}
