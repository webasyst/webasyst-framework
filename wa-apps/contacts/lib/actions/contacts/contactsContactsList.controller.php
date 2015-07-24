<?php

/** Everything that shows lists of contacts uses this controller. */
class contactsContactsListController extends waJsonController
{
    protected $offset, $limit;
    protected $sort, $order;
    protected $filters;


    protected function prepare()
    {
        $this->offset = waRequest::post('offset', 0, 'int');
        $this->limit = waRequest::post('limit', 20, 'int');
        if (!$this->limit) {
            $this->limit = 30;
        }
        $this->order = '';
        if ($this->sort = waRequest::post('sort')) {
            $this->order = waRequest::post('order', 1, 'int') ? ' ASC' : ' DESC';
        }
    }

    protected function getCollection($h) {
        return new contactsCollection($h);
    }

    public function execute()
    {
        $this->prepare();

        if ( ( $query = trim(waRequest::post('query'), '/'))) {
            $query = urldecode($query);
            if (strpos($query, '/') === false) {
                $h = $hash = 'search/'.$query;
            } else {
                $h = $hash = $query;
                if (substr($hash, 0, 14) == 'import/results') {
                    $h = str_replace('import/results', 'import', $hash);
                }
            }
        } else {
            $h = $hash = '';
        }

        $h_parts = explode('/', $h, 2);

        $collection = new contactsCollection($h);

        $this->response['fields'] = array();
        $fields = '*,photo_url_32,photo_url_96';
        if ($h_parts[0] === 'users' || $h_parts[0] === 'group') {
            if (!wa()->getUser()->isAdmin()) {
                throw new waRightsException(_w('Access denied'));
            }
            $fields .= ',_access';
            $this->response['fields']['_access'] = array(
                'id' => '_access',
                'name' => _w('Access'),
                'type' => 'Access',
                'vertical' => true
            );
        }

        $collection->orderBy($this->sort, $this->order);
        $this->response['count'] = $collection->count();

        $view = waRequest::post('view');

        if ($view == 'list') {
            // Preload info to cache to avoid excess DB access
            $cm = new waCountryModel();
            $cm->preload();
        }

        $this->response['contacts'] = array_values(
            $collection->getContacts($fields, $this->offset, $this->limit)
        );
        $this->workupContacts($this->response['contacts']);
        $this->response['total_count'] = $collection->count();
        foreach ($this->response['contacts'] as $i => &$c) {
            $c['offset'] = $this->offset + $i;
        }
        unset($c);

        if ($view == 'list') {
            // Need to format field values correctly for this view.
            foreach($this->response['contacts'] as &$cdata) {
                $c = new waContact($cdata['id']);
                $c->setCache($cdata);
                $data = $c->load('list,js') + $cdata;
                contactsHelper::normalzieContactFieldValues($data, waContactFields::getInfo($c['is_company'] ? 'company' : 'person', true));
                if (isset($data['photo'])) {
                    $data['photo'] = $c->getPhoto();
                }
                $c->removeCache(array_keys($cdata));
                $cdata = $data;
            }

            $this->response['fields'] = array_merge($this->response['fields'], contactsHelper::getFieldsDescription(array(
                'title',
                'name',
                'photo',
                'firstname',
                'middlename',
                'lastname',
                'locale',
                'timezone',
                'jobtitle',
                'company',
                'sex',
                'company_contact_id'
            ), true));

            unset($cdata);
        } else {
            foreach ($this->response['contacts'] as &$cdata) {
                $cdata['name'] = waContactNameField::formatName($cdata);
                if ($cdata['name'] == $cdata['id']) {
                    $cdata['name'] = false;
                }
            }
            unset($cdata);
        }

        // for companies set name to company name
        // for contacts with empty name, set it to <no name>
        foreach($this->response['contacts'] as &$c) {
            if (isset($c['name']) && trim($c['name'])) {
                continue;
            }

            if (isset($c['company']) && trim($c['company'])) {
                $c['name'] = $c['company'];
                unset($c['company']);
                continue;
            }

            $c['name'] = '<'._w('no name').'>';
        }
        unset($c);

        $title = $collection->getTitle();

        $hm = new contactsHistoryModel();

        if ($hash) {
            $type = explode('/', $hash);
            $hash = substr($hash, 0, 1) == '/' ? $hash : '/contacts/'.$hash;
            $type = $type[0];

            // if search query looks like a quick search then remove field name from header
            if ($type == 'search' && preg_match('~^/contacts/search/(name\*=[^/]*|email\*=[^/]*@[^/]*)/?$~i', $hash)) {
                $title = preg_replace("~^[^=]+=~", '', $title);
            }

            // save history
            if ($type == 'search') {
                $hm->save($hash, $title, $type, $this->response['count']);
                $this->logAction('search');
            }

            // Information about system category in categories view
            if (substr($hash, 0, 19) === '/contacts/category/') {
                $category_id = (int) substr($hash, 19);
                $cm = new waContactCategoryModel();
                $category = $cm->getById($category_id);
                if ($category && $category['system_id']) {
                    $this->response['system_category'] = $category['system_id'];
                }
            }
        }

        // Update history in user's browser
        $this->response['history'] = $hm->get();
        $this->response['title'] = $title;
    }

    public function workupContacts(&$contacts)
    {
        if (!$contacts) {
            return array();
        }
        $contact_fields = array(
            array_keys(waContactFields::getAll('person', true)),
            array_keys(waContactFields::getAll('company', true)),
        );
        foreach ($contacts as &$c) {
            $fields = $contact_fields[intval($c['is_company'])];
            $data = array(
                'id' => $c['id']
            );
            foreach ($fields as $fld_id) {
                if (array_key_exists($fld_id, $c)) {
                    $data[$fld_id] = $c[$fld_id];
                    unset($c[$fld_id]);
                }
            }
            $c = array_merge($data, $c);
        }
        unset($c);

        // load that fields, that are top
        if ($this->getRequest()->request('top')) {
            foreach ($contacts as &$c) {
                $contact = new waContact($c['id']);
                $c['top'] = $contact->getTopFields();
            }
            unset($c);
        }
    }

}

// EOF