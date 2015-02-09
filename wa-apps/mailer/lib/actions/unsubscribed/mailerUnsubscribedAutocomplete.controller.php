<?php
/**
 * Return autocomplete strings for advanced search.
 */
class mailerUnsubscribedAutocompleteController extends waController
{
    public function execute()
    {
        $term = waRequest::request('term', '', 'string');
        $limit = waRequest::request('limit', 5, 'int');
        if (!strlen($term) || $limit <= 0) {
            echo '[]';
            return;
        }

        $m = new waModel();

        // The plan is: try queries one by one (starting with fast ones),
        // until we find $limit rows total.
        $sqls = array();

        // Email starts with requested string
        $sqls[] = "SELECT e.email, c.id, c.name
                   FROM wa_contact_emails AS e
                       JOIN wa_contact AS c
                           ON c.id=e.contact_id
                   WHERE e.email LIKE '".$m->escape($term, 'like')."%'
                   LIMIT {LIMIT}";

        // Email contains requested string
        $sqls[] = "SELECT e.email, c.id, c.name
                   FROM wa_contact_emails AS e
                       JOIN wa_contact AS c
                           ON c.id=e.contact_id
                   WHERE e.email LIKE '%".$m->escape($term, 'like')."%'
                   LIMIT {LIMIT}";

        $result = array();
        $term_safe = htmlspecialchars($term);
        foreach($sqls as $sql) {
            $lim = $limit - count($result);
            if ($lim <= 0) {
                break;
            }
            foreach($m->query(str_replace('{LIMIT}', $lim, $sql)) as $c) {
                $email = $this->prepare(ifset($c['email'], ''), $term_safe);
                $result[$c['id']] = array(
                    'value' => $c['email'],
                    'email' => $c['email'],
                    'name' => $c['name'],
                    'label' => $email,
                    'id' => $c['id'],
                );
            }
        }

        echo json_encode(array_values($result));
        exit;
    }

    protected function prepare($str, $term_safe)
    {
        return preg_replace('~('.preg_quote($term_safe, '~').')~ui', '<span class="bold highlighted">\1</span>', htmlspecialchars($str));
    }
}

