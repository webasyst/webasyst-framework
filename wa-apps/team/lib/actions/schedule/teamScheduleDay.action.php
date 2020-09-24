<?php

class teamScheduleDayAction extends teamCalendarAction
{
    public function execute()
    {
        $date = waRequest::post('date', null, waRequest::TYPE_STRING_TRIM);
        $ids = waRequest::post('id', array(), waRequest::TYPE_ARRAY_TRIM);

        $cem = new waContactEventsModel();

        $unsorted_events = $cem->select('*')->where("id IN('".join("', '", $cem->escape($ids))."')")->fetchAll('id');
        $events = array();
        foreach ($ids as $id) {
            if (!empty($unsorted_events[$id])) {
                $events[$id] = $unsorted_events[$id];
            }
        }
        $users = teamHelper::getUsers();
        $day = date('Y-m-d', strtotime($date));

        foreach ($users as $id => $u) {
            if (empty($u['birth_day']) || empty($u['birth_month'])) {
                continue;
            }
            $u['birthday'] = date('Y', strtotime($date))
                .'-'.str_pad($u['birth_month'], 2, '0', STR_PAD_LEFT)
                .'-'.str_pad($u['birth_day'], 2, '0', STR_PAD_LEFT);
            if ($u['birthday'] < $day || $u['birthday'] > $day) {
                continue;
            } else {
                $events['birthday'.$id] = array(
                    'id'          => null,
                    'calendar_id' => 'birthday',
                    'contact_id'  => $id,
                    'name'        => _w('Birthday'),
                    'bg_color'    => null,
                    'font_color'  => null,
                    'icon_class'  => 'cake',
                    'start'       => $date,
                    'end'         => $date,
                    'is_allday'   => 1,
                    'is_status'   => null,
                );
            }
        }

        /*
        $day = array(
            "month_name" => "Август",
            "number" => 4
        );

        $events = array(
            array(
                "id" => rand(1,5000),
                "style_class" => "type-0",
                "icon_class" => "email",
                "name" => "Переговоры",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-1",
                "icon_class" => "phone",
                "name" => "Тусуемся на Бали",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-2",
                "icon_class" => "phone",
                "name" => "Тусуемся на Бали",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-3",
                "icon_class" => "userpic20",
                "icon_image" => "/wa-data/public/contacts/photos/12/95/1019512/1291370737.40x40.jpg",
                "name" => "Пятница, 100 грамм",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-4",
                "name" => "Смотрю кино, после 100 грамм",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-2",
                "icon_class" => "phone",
                "name" => "Тусуемся на Бали",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-3",
                "icon_class" => "userpic20",
                "icon_image" => "/wa-data/public/contacts/photos/12/95/1019512/1291370737.40x40.jpg",
                "name" => "Пятница, 100 грамм",
            ),
            array(
                "id" => rand(1,5000),
                "style_class" => "type-4",
                "name" => "Смотрю кино, после 100 грамм",
            ),
        );
        */

        $this->view->assign(array(
            'day'       => $date,
            'events'    => $events,
            'calendars' => teamCalendar::getCalendars(),
            'users'     => $users,
        ));
    }
}
