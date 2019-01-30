<?php

class teamApitokensAction extends teamContentViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            throw new waException(_w('Access denied'));
        }

        /* PAGE */
        $page = waRequest::request('page', 1);
        $list_params['limit'] = teamConfig::ROWS_PER_PAGE;
        $list_params['offset'] = max(0, $page - 1) * $list_params['limit'];
        $total_count = 0;

        $api_token_model = new waApiTokensModel();
        $api_tokens = $api_token_model->getList($list_params, $total_count);

        // Get contacts
        $contact_ids = array();
        foreach ($api_tokens as $token) {
            if ($token['contact_id']) {
                $contact_ids[] = $token['contact_id'];
            }
        }
        $contacts = $this->getContactsByIds($contact_ids);

        // Get apps
        $apps = wa()->getApps();

        foreach ($api_tokens as &$token) {
            // Add scope apps images and names
            $token['installed_apps'] = $token['not_installed_apps'] =  array();
            $token_apps = explode(',', $token['scope']);
            foreach ($token_apps as $app) {
                if (array_key_exists($app, $apps)) {
                    $token['installed_apps'][] = array(
                        'img' => ifempty($apps[$app]['img']),
                        'name'  => ifempty($apps[$app]['name'], $app),
                    );
                } else {
                    $token['not_installed_apps'][] = $app;
                }
            }

            // Add contact
            if (isset($contacts[$token['contact_id']])) {
                /** @var waContact $contact */
                $contact = $contacts[$token['contact_id']];
                $token['contact'] = array(
                    'id'    => $contact->getId(),
                    'name'  => $contact->getName(),
                    'login' => $contact->get('login'),
                    'photo' => $contact->getPhoto(16, 16),
                );
            }
        }
        unset($token);

        $this->view->assign(array(
            'api_tokens'  => $api_tokens,
            'page'        => $page,
            'total_count' => $total_count,
        ));
    }

    protected function getContactsByIds($ids)
    {
        $ids = (array)$ids;

        if (!$ids) {
            return array();
        }

        $contacts = array();

        $col = new waContactsCollection('/id/'.join(',', $ids));
        $col = $col->getContacts('photo_url_16');
        foreach ($col as $contact) {
            $contacts[$contact['id']] = new waContact($contact);
        }
        return $contacts;
    }
}