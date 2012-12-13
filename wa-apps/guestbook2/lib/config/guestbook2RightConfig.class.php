<?php

/**
 * Definition of the app's extended access rights which can be assigned to users and user groups in the Contacts app
 * Описание детальных прав приложения, которые можно задать пользователям и группам в приложении Контакты
 * @see http://www.webasyst.com/framework/docs/dev/access-rights/
 */
class guestbook2RightConfig extends waRightConfig
{
    public function init()
    {
        // The right to delete records
        // Право удалять записи
        $this->addItem('delete', 'Can delete posts', 'checkbox');

        // The right to edit design (templates and themes)
        // Право редактировать дизайн (шаблоны и темы)
        $this->addItem('design', _ws('Can edit design'), 'checkbox');
    }
}