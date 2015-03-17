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
        
        $type = waRequest::request('type', null, waRequest::TYPE_STRING_TRIM);
        
        $model = new waModel();
        if(strpos($term, '@') !== FALSE) {
            $contacts = new contactsCollection('/search/email*='.$term);
        } else {
            $contacts = new contactsCollection();
            $t_a = preg_split("/\s+/", $term);
            $cond = array();
            foreach ($t_a as $t) {
                $t = trim($t);
                if ($t) {
                    $t = $model->escape($t, 'like');
                    if ($type === 'person') {
                        $cond[] = "(c.firstname LIKE '{$t}%' OR c.middlename LIKE '{$t}%' OR c.lastname LIKE '{$t}%')";
                    } else if ($type === 'company') {
                        $cond[] = "c.name LIKE '{$t}%'";
                    } else {
                        $cond[] = "(c.firstname LIKE '{$t}%' OR c.middlename LIKE '{$t}%' OR c.lastname LIKE '{$t}%' OR c.name LIKE '{$t}%')";
                    }
                }
            }
            if ($cond) {
                $contacts->addWhere(implode(" AND ", $cond));
            }
        }
        
        if ($type) {
            if ($type === 'person') {
                $contacts->addWhere("is_company = 0");
            } else if ($type === 'company') {
                $contacts->addWhere("is_company = 1");
            }
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
        $str = htmlspecialchars($str);
        $reg = array();
        foreach (preg_split("/\s+/", $term_safe) as $t) {
            $t = trim($t);
            if ($t) {
                $reg[] = preg_quote($t, '~');
            }
        }
        if ($reg) {
            $reg = implode('|', $reg);
            $str = preg_replace('~('.$reg.')~ui', '<span class="bold highlighted">\1</span>', $str);
        }
        return $str;
    }
}

