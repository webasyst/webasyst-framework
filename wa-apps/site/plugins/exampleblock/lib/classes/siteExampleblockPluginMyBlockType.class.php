<?php
/**
 * Класс, унаследованный от siteBlockType (и соответствующий шаблон рядом в templates/*.html),
 * реализует логику работы кастомного блока. Этот блок плагина ничего интересного не делает,
 * а примеры лучше посмотреть в блоках в приложении Сайт.
 */
class siteExampleblockPluginMyBlockType extends siteBlockType
{
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Example'),
            'sections' => [
                [   'type' => 'VisibilityGroup',
                    'name' => _w('Visibility on devices'),
                ],
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}