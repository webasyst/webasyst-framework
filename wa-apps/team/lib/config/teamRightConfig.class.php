<?php

class teamRightConfig extends waRightConfig
{
    const RIGHT_NONE = -1;
    const RIGHT_READ = 0;
    const RIGHT_READ_WRITE = 1;

    /**
     * @var waContactRightsModel
     */
    protected static $model = null;

    protected static $groups = array();

    public function init()
    {
        if (!self::$model) {
            self::$model = new waContactRightsModel();
        }
        if (!self::$groups) {
            $gm = new waGroupModel();
            self::$groups = $gm->getNames();
        }
        $not_admin_groups = $this->getNotAdminGroupNames();
        $cm = new waContactCalendarsModel();

        // Calendars sorted properly
        $calendars = array();
        foreach(teamCalendar::getCalendars(false) as $c) {
            if ($c['is_limited']) {
                $calendars[$c['id']] = $c['name'];
            }
        }

        $this->addItem('edit_self', _w('Can edit self contact info'), 'always_enabled');

        $this->addItem('add_users', _w('Can add users'), 'checkbox');
        $this->addItem('add_groups', _w('Can add groups'), 'checkbox');

        $options = array(
            self::RIGHT_NONE       => _w('No access'),
            self::RIGHT_READ       => _w('View only'),
            self::RIGHT_READ_WRITE => _w('Edit users data'),
        );
        $control = array(
            'items'    => self::$groups,
            'position' => 'right',
            'options'  => $options,
        );
        $this->addItem(
            'manage_users_in_group',
            _w('Can manage users in groups'),
            'selectlist',
            $control
        );
        $this->addItem(
            'manage_group',
            _w('Can manage groups'),
            'list',
            array('items' => $not_admin_groups, 'hint1' => 'all_checkbox')
        );
        if ($calendars) {
            $this->addItem(
                'edit_events_in_calendar',
                _w('Can edit events in calendars'),
                'list',
                array('items' => $calendars, 'hint1' => 'all_checkbox')
            );
        }
        wa()->event('rights.config', $this);

    }

    /**
     * Returns associative array of group names with key group id sorted by name
     *
     * @return array
     */
    protected function getNotAdminGroupNames()
    {
        $sql = "SELECT g.id, g.name
                FROM wa_group g
                    LEFT JOIN wa_contact_rights r
                        ON r.group_id=g.id
                            AND r.app_id='webasyst'
                            AND r.name='backend'
                            AND r.value=1
                WHERE r.app_id IS NULL
                ORDER BY g.type, g.sort, g.name";
        return self::$model->query($sql)->fetchAll('id', true);
    }
}
