<?php

/**
 * Фронтенд приложения Гостевая книга 2
 * @see http://www.webasyst.com/ru/framework/docs/dev/frontend-routing/
 * @see http://www.webasyst.com/ru/framework/docs/dev/controllers/
 */
class guestbook2FrontendAction extends waViewAction
{
    public function execute()
    {
        // Задаём лайаут для фронтенда
        $this->setLayout(new guestbook2FrontendLayout());
        // Задаём шаблон темы
        $this->setThemeTemplate('guestbook.html');

        // Если пришёл POST-запрос, то нужно записать в БД новую запись
        if (waRequest::method() == 'post') {
            $this->add();
        }
        // Создаем экземпляр модели для получения данных из БД
        $model = new guestbook2Model();

        // Получаем количество записей на одной странице из настроек приложения
        $limit = $this->getConfig()->getOption('records_per_page');
        // Текущая страница
        $page = waRequest::param('page');
        if (!$page) {
            $page = 1;
        }
        $this->view->assign('page', $page);
        // Вычисляем смещение
        $offset = ($page - 1) * $limit;
        // Получаем записи гостевой книги из БД
        $records = $model->getRecords($offset, $limit);
        // Всего записей
        $records_count = $model->countAll();
        $pages_count = ceil($records_count / $limit);
        $this->view->assign('pages_count', $pages_count);
        // Подготавливаем записи для передачи в шаблон темы
        foreach ($records as &$r) {
            if ($r['contact_id']) {
                $r['name'] = htmlspecialchars($r['contact_name']);
                // получаем URL на фотографию контакта
                $r['photo_url'] = waContact::getPhotoUrl($r['contact_id'], $r['photo'], 20);
            } else {
                $r['name'] = htmlspecialchars($r['name']);
            }
            $r['text'] = nl2br(htmlspecialchars($r['text']));
        }
        unset($r);
        // Передаем записи в шаблон
        $this->view->assign('records', $records);
        // Часть урла для ссылок на страницы
        $this->view->assign('url', wa()->getRouteUrl('/frontend'));
    }

    /**
     * Добавление новой записи в гостевую книгу
     */
    protected function add()
    {
        // Создаем экземпляр модели для получения данных из БД
        $model = new guestbook2Model();
        if ($text = waRequest::post('text')) {
            $data = array(
                'text' => $text,
                'datetime' => date('Y-m-d H:i:s')
            );
            if ($this->getUser()->getId()) {
                $data['contact_id'] = $this->getUser()->getId();
            } else {
                $data['name'] = waRequest::post('name');
            }
            // Вставляем новую запись в таблицу
            $model->insert($data);
            // Если контакт не является юзером бэкенда
            if ($this->getUser()->getId() && !$this->getUser()->get('is_user'))  {
                // Добавляем контакт в системную категорию приложения
                $this->getUser()->addToCategory($this->getAppId());
            }
        }
        // редирект на первую страницу, чтобы показать новое сообщение
        $this->redirect(wa()->getRouteUrl('/frontend'));
    }
}