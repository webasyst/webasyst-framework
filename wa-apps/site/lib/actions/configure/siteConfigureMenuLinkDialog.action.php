<?php

class siteConfigureMenuLinkDialogAction extends waViewAction
{
    public function execute()
    {
        $domain_id = waRequest::get('domain_id');
        $id = waRequest::get('id', '');

        $link = $this->getLink($id);
        $link_url = ifset($link, 'base_url', ifset($link, 'url', null));

        $default_type = strlen($id) ? siteMenuLinksConfig::CUSTOM_LINK_TYPE : siteMenuLinksConfig::SITEMAP_LINK_TYPE;
        $selected_type = ifempty($link, 'type', $default_type);
        $selected_row_index = null;
        $types = siteMenuLinksConfig::getLinkTypes();

        list($table_rows) = (new siteMapOverviewAction())->getSitemapTreeData();
        $root_url = wa()->getConfig()->getRootUrl();
        foreach($table_rows as $i => &$row) {
            unset($row['children'], $row['pages']);
            if (isset($row['full_url'])) {
                if (isset($row['route'])) {
                    $full_url = rtrim($row['route'], '*').$row['full_url'];
                } else {
                    $full_url = rtrim($row['full_url'], '/');
                    if ($full_url) {
                        $full_url .= '/';
                    }
                }
            } elseif (isset($row['url_formatted'])) {
                $full_url = ltrim($row['url_formatted'], '/');
            } else {
                continue;
            }

            $full_url = $root_url.ltrim($full_url, '/');
            $row['frontend_link'] = $full_url;

            if ($full_url === $link_url && !isset($selected_row_index)) {
                $selected_row_index = $i;
                if ($selected_type === $default_type) {
                    $selected_type = siteMenuLinksConfig::SITEMAP_LINK_TYPE;
                }
            }
        }
        unset($row);

        $this->view->assign([
            'domain_id' => $domain_id,
            'id' => $id,
            'link' => $link,
            'types' => $types,
            'selected_row_index' => $selected_row_index,
            'selected_type' => $selected_type,
            'table_rows' => $table_rows,
        ]);
    }

    /**
     * @param string|int $id
     * @return array
     */
    private function getLink($id)
    {
        $link = [];
        $links = siteMenuLinksConfig::getDomainLinks();
        if (!empty($links[$id])) {
            $link = $links[$id];
        }

        $link['rel'] = array_filter(explode(' ', ifset($link, 'rel', '')), function ($val) { return $val; });

        return $link;
    }
}
