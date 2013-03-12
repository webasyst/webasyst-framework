<?php

/**
 * Implements autocomplete for contact search fields in other apps.
 */
class contactsBackendAutocompleteController extends waJsonController
{
    public function execute()
    {
        $term = waRequest::request('term');
        $limit = waRequest::request('limit', 30, 'int');
        if(mb_strlen($term) < 2) {
            return;
        }

        if(strpos($term, '@') !== FALSE) {
            $contacts = new contactsCollection('/search/email*='.$term);
        } else {
            $contacts = new contactsCollection('/search/name*='.$term);
        }

        $this->response = array();
        $term_safe = htmlspecialchars($term);
        foreach($contacts->getContacts('id,name,company,email', 0, $limit) as $c) {
            $name = $this->prepare($c['name'],$term_safe);
            $email = $this->prepare(ifset($c['email'][0], ''),$term_safe);
            $company = $this->prepare($c['company'],$term_safe);

            $this->response[] = array(
                'label' => implode(', ', array_filter(array($name, $company, $email))),
                'value' => $c['id'],
                'name' => $c['name'],
                'email' => ifset($c['email'][0], ''),
                'company' => $c['company'],
            );
        }
    }

    public function display()
    {
        $this->getResponse()->sendHeaders();
        echo json_encode($this->response);
    }

    protected function prepare($str, $term_safe)
    {
        return preg_replace('~('.preg_quote($term_safe, '~').')~ui', '<span class="bold highlighted">\1</span>', htmlspecialchars($str));
    }
}

