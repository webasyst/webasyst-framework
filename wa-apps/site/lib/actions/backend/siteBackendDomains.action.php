<?php
/**
 * App main page for UI 2.0. List of sites (domains).
 */
class siteBackendDomainsAction extends waViewAction
{
    public function execute()
    {
        $domain_change_pending = (new waAppSettingsModel())->get('hosting', 'domain_change_pending');
        $new_domain_name = null;
        if ($domain_change_pending) {
            $domain_change_pending = @json_decode($domain_change_pending, true);
            if (!empty($domain_change_pending['new_domain_name'])) {
                $new_domain_name = mb_strtolower($domain_change_pending['new_domain_name']);
            }
        }

        $domains = siteHelper::getDomains(true);

        foreach ($domains as &$d) {
            if (empty($d['is_alias'])) {
                $d['is_pending'] = $new_domain_name === mb_strtolower($d['name']);
                foreach (wa()->getRouting()->getRoutes($d['name']) as $r) {
                    if ($r['url'] === '*' && isset($r['redirect']) && substr($r['redirect'], 0, 4) === 'http') {
                        $d['redirect'] = $r['redirect'];
                        break;
                    }
                }
            }

            $path = wa()->getDataPath(null, true).'/data/'.$d['name'].'/favicon.ico';
            if (file_exists($path)) {
                $d['favicon'] = wa()->getDataUrl('data/'.$d['name'].'/favicon.ico', true);
            } else {
                $path = 'http'.(waRequest::isHttps() ? 's' : '').'://'.$d['name'].'/favicon.ico';
                if (file_exists($path)) {
                    $d['favicon'] = $path;
                }
            }
        }

        $this->prepareData($domains);

        $sort_types = [
            'created:asc' => [
                'title' => _w('Added later'),
                'icon' => '<i class="fas fa-sort-amount-down-alt"></i>'
            ],
            'created:desc' => [
                'title' => _w('Added earlier'),
                'icon' => '<i class="fas fa-sort-amount-down"></i>'
            ],
            'name:asc' => [
                'title' => _w('By name A–Z'),
                'icon' => '<i class="fas fa-sort-alpha-down"></i>'
            ],
            'name:desc' => [
                'title' => _w('By name Z–A'),
                'icon' => '<i class="fas fa-sort-alpha-down-alt"></i>'
            ],
        ];

        $sort = $this->getSort();
        if (empty($sort_types[$sort])) {
            $sort = 'created:desc';
        }

        $this->makeSort($domains, $sort);

        $this->view->assign([
            'domains'       => $domains,
            'is_list_view'  => wa()->getUser()->getSettings('site', 'list_view') == 1,
            'sort'          => $sort,
            'sort_types'    => $sort_types,
        ]);
    }

    private function prepareData(&$domains)
    {
        foreach ($domains as &$d) {
            $d['title'] = str_replace('www.', '', waIdna::dec($d['title']));
        }
    }

    private function makeSort(&$domains, $sort_by)
    {
        $sortName = function ($asc = true) {
            return function ($a, $b) use ($asc) {
                $a = $a['title'];
                $b = $b['title'];
                $n = ($asc ? 1 : -1);

                $has_num_a = preg_match('/^\d+/i', $a);
                $has_num_b = preg_match('/^\d+/i', $b);
                if ($has_num_a || $has_num_b) {
                    if ($has_num_a && !$has_num_b) {
                        return -1 * $n;
                    } elseif (!$has_num_a && $has_num_b) {
                        return 1 * $n;
                    }
                }

                $begins_in_cyrillic_a  = preg_match('/^[а-яА-ЯЁё]/u', $a);
                $begins_in_cyrillic_b = preg_match('/^[а-яА-ЯЁё]/u', $b);
                if ($begins_in_cyrillic_a && !$begins_in_cyrillic_b) {
                    return -1 * $n;
                } elseif (!$begins_in_cyrillic_a && $begins_in_cyrillic_b) {
                    return 1 * $n;
                }

                return strcmp($a, $b) * $n;
            };
        };

        switch ($sort_by) {
            case 'name:asc':
                uasort($domains, $sortName(true));
                break;
            case 'name:desc':
                uasort($domains, $sortName(false));
                break;
            case 'created:asc':
                $domains = array_reverse($domains, true);
                break;
        }
    }

    private function getSort()
    {
        $setting_key = 'domain_list_sort';
        $sort = waRequest::get('sort');
        if ($sort) {
            $this->getUser()->setSettings('site', $setting_key, $sort);
        } else {
            $sort = $this->getUser()->getSettings('site', $setting_key);
        }

        return $sort;
    }
}
