<?php

class webasystAnalyticsConfig extends waAnalyticsConfig
{
    public function getEntities()
    {
        return array(
            'log' => array(
                'name' => 'Журнал действий',
                'fields' => array(
                    'datetime' => array(
                        'table' => 'wa_log',
                        'field' => 'DATE(:table.datetime)',
                        'name' => 'Дата',
                    ),
                    'app_id' => array(
                        'table' => 'wa_log',
                        'field' => 'app_id',
                        'name' => 'Приложение',
                    ),
                    'action' => array(
                        'table' => 'wa_log',
                        'field' => 'action',
                        'name' => 'Действие',
                    ),
                    'count' => array(
                        'new' => true,
                        'table' => 'wa_log',
                        'select' => 'COUNT(DISTINCT :table.id)',
                        'name' => 'Количество действий',
                    )
                ),
                'tables' => array(
                    'wa_log' => array(
                        'key' => 'id'
                    )
                ),
                'roles' => array(
                    'contacts' => array(
                        'contact' => array(
                            'actor' => array(
                                'name' => 'Исполнитель',
                                'key' => array(
                                    'table' => 'wa_log',
                                    'field' => 'contact_id'
                                )
                            )
                        )
                    )
                )
            )
        );
    }
}