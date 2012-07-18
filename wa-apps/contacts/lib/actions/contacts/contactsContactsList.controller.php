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
        $collection = $this->getCollection($h);
        $collection->orderBy($this->sort, $this->order);
        $this->response['count'] = $collection->count();
        $view = waRequest::post('view');
        switch ($view) {
            case 'list':
                $fields = '*';
                break;
            case 'thumbs':
                $fields = 'id,name,photo';
                break;
            case 'table':
            default:
                $fields = waRequest::post('fields');
        }

        if ($view == 'list') {
            // Preload info to cache to avoid excess DB access
            $cm = new waCountryModel();
            $cm->preload();
        }

        if ($hash && $fields != '*') {
            if ( ( $wf = $collection->getWhereFields())) {
                $fields = $fields.",".implode(",", $wf);
            }
            $this->response['fields'] = explode(',', $fields);
        }

        $this->response['contacts'] = array_values($collection->getContacts($fields, $this->offset, $this->limit));

        if ($view == 'list') {
            // Need to format field values correctly for this view.
            foreach($this->response['contacts'] as &$cdata) {
                $c = new waContact($cdata['id']);
                $c->setCache($cdata);
                $data = $c->load('list,js') + $cdata;
                if (isset($data['photo'])) {
                    $data['photo'] = $c->getPhoto();
                }
                $c->removeCache(array_keys($cdata));
                $cdata = $data;
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

        if ($hash) {
            $type = explode('/', $hash);
            $hash = substr($hash, 0, 1) == '/' ? $hash : '/contacts/'.$hash;
            $type = $type[0];

            // if search query looks like a quick search then remove field name from header
            if ($type == 'search' && preg_match('~^/contacts/search/(name\*=[^/]*|email\*=[^/]*@[^/]*)/?$~i', $hash)) {
                $title = preg_replace("~^[^=]+=~", '', $title);
            }

            // save history
            if ($type == 'search' || $type == 'import') {
                $history = new contactsHistoryModel();
                if ($history->save($hash, $title, $type, $this->response['count'])) {
                    // new search performed, save to statistics log
                    $this->log('search', 1);
                }
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
        $historyModel = new contactsHistoryModel();
        $this->response['history'] = $historyModel->get();
        $this->response['title'] = $title;
    }
}

// EOF