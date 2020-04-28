<?php
/**
 * Contact info editor tab in profile.
 */
class teamProfileInfoAction extends waViewAction
{
    /**
     * @var waContact|waUser
     */
    protected $contact;
    protected $id;

    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!empty($this->params['id'])) {
            $this->id = $this->params['id'];
            $this->contact = new waContact($this->id);
        }
        if (!$this->contact || !$this->contact->exists()) {
            throw new waException('Contact not found', 404);
        }
    }

    public function execute()
    {
        $can_edit = $this->canEdit();
        $this->getContactInfo($can_edit);

        $this->view->assign('can_edit', $can_edit);
        $this->view->assign('save_url', $this->getSaveUrl($can_edit));
        $this->view->assign('is_my_profile', $this->id == wa()->getUser()->getId());

        $this->view->assign('is_admin', wa()->getUser()->isAdmin('team'));
        $this->view->assign('is_superadmin', wa()->getUser()->isAdmin());

        $this->view->assign('geocoding', $this->getGeocodingOptions());

        $this->view->assign('assets', $this->getAssets());
    }

    protected function getAssets()
    {
        $wa_url = wa()->getRootUrl();
        return array(
            "{$wa_url}wa-content/js/jquery-ui/jquery.ui.core.min.js",
            "{$wa_url}wa-content/js/jquery-ui/jquery.ui.widget.min.js",
            "{$wa_url}wa-content/js/jquery-ui/jquery.ui.mouse.min.js",
            "{$wa_url}wa-content/js/jquery-ui/jquery.ui.sortable.min.js"
        );
    }

    protected function canEdit()
    {
        return teamUser::canEdit($this->id);
    }

    protected function getSaveUrl($can_edit)
    {
        if ($can_edit === 'limited_own_profile') {
            $app_url = wa()->getConfig()->getBackendUrl(true);
        } else {
            $app_url = wa()->getUrl();
        }
        return $app_url.'?module=profile&action=save';
    }

    /** Using $this->id get waContact and save it in $this->contact;
      * Load vars into $this->view specific to waContact. */
    protected function getContactInfo($can_edit)
    {
        $user = wa()->getUser();
        if ($this->id == $user->getId()) {
            $this->contact = $user;
            $this->view->assign('own_profile', true);
        } else {
            $this->contact = new waContact($this->id);
            $this->view->assign('own_profile', false);
        }

        $this->view->assign('contact', $this->contact);

        // who created this contact and when
        $this->view->assign('contact_create_time', waDateTime::format('datetime', $this->contact['create_datetime'], $user->getTimezone()));
        if ($this->contact['create_contact_id']) {
            try {
                $author = new waContact($this->contact['create_contact_id']);
                if ($author['name']) {
                    $this->view->assign('author', $author);
                }
            } catch (Exception $e) {
                // Contact not found. Ignore silently.
            }
        }

        $this->view->assign('top', $this->contact->getTopFields());

        // Main contact editor data
        $fieldValues = $this->contact->load('js', true);
        if (!empty($fieldValues['company_contact_id'])) {
            $m = new waContactModel();
            if (!$m->getById($fieldValues['company_contact_id'])) {
                $fieldValues['company_contact_id'] = 0;
                $this->contact->save(array('company_contact_id' => 0));
            }
        }

        $contactFields = waContactFields::getInfo($this->contact['is_company'] ? 'company' : 'person', true);

        // Only show fields that are allowed in own profile
        if ($can_edit === 'limited_own_profile') {
            $allowed = array();
            foreach (waContactFields::getAll('person') as $f) {
                if ($f->getParameter('allow_self_edit')) {
                    $allowed[$f->getId()] = true;
                }
            }

            $fieldValues = array_intersect_key($fieldValues, $allowed);
            $contactFields = array_intersect_key($contactFields, $allowed);
        }

        // Normalize field values
        foreach ($contactFields as $field_info) {
            if (is_object($field_info) && $field_info instanceof waContactField) {
                $field_info = $field_info->getInfo();
            }
            if ($field_info['multi'] && isset($fieldValues[$field_info['id']])) {
                $fieldValues[$field_info['id']] = array_values($fieldValues[$field_info['id']]);
            }
            if ($field_info['id'] === 'timezone') {
                // This hack is here rather than correct definition in waContactTimezoneField
                // because of backwards compatibility with older version of Contacts app
                // that does not know nothing about special Timezone field type.
                $contactFields[$field_info['id']]['type'] = 'Timezone';
            }
        }

        $this->view->assign('contactFields', $contactFields);
        $this->view->assign('contactFieldsOrder', array_keys($contactFields));
        $this->view->assign('fieldValues', $fieldValues);

        // Contact categories
        $cm = new waContactCategoriesModel();
        $this->view->assign('contact_categories', array_values($cm->getContactCategories($this->id)));

    }

    protected function getGeocodingOptions()
    {
        $map_options = array(
            'type' => '',
            'key' => ''
        );

        try {
            $map = wa()->getMap();
            if ($map->getId() === 'google') {
                $map_options = array(
                    'type' => $map->getId(),
                    'key' => $map->getSettings('key')
                );
            } elseif ($map->getId() === 'yandex') {
                $map_options = array(
                    'type' => $map->getId(),
                    'key' => $map->getSettings('apikey')
                );
            }
        } catch (waException $e) {

        }

        return $map_options;
    }
}
