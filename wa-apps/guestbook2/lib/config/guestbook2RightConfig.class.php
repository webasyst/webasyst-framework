<?php

/**
 * Описание детальных прав приложения, которые можно задать пользователям и группам в приложении контакты
 * @see http://www.webasyst.com/ru/framework/docs/dev/access-rights/
 */
class guestbook2RightConfig extends waRightConfig
{
    public function init()
    {
        // Право удалять записи
        $this->addItem('delete', 'Can delete posts', 'checkbox');

        // Право редактировать дизайн (шаблоны и темы)
        $this->addItem('design', _ws('Can edit design'), 'checkbox');
    }
}