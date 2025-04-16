<?php

class siteFrontendBlockpageAction extends waViewAction
{
    const THEME_FILE = 'blockpage.layout.html';

    public function execute()
    {
        $page = waRequest::param('page');
        $page_params = waRequest::param('page_params');

        if (!$page) {
            // this should not happen
            throw new waException('Block page not found', 500);
        }

        $this->setLastModified($page);

        // set response
        if (!$this->getResponse()->getTitle() && isset($page['title'])) {
            $this->getResponse()->setTitle($page['title']);
        }
        $this->getResponse()->setMeta(array(
            'keywords' => isset($page_params['meta_keywords']) ? $page_params['meta_keywords'] : '',
            'description' => isset($page_params['meta_description']) ? $page_params['meta_description'] : ''
        ));

        if (ifset($page_params, 'og_active', false)) {
            foreach (ifset($page_params, array()) as $property => $content) {
                if ($content && $property !== 'og_active') {
                    substr($property, 0, 3) == 'og_' && wa()->getResponse()->setOGMeta('og:'.substr($property, 3), $content);
                }
            }
        }

        // Use theme defined by the blockpage
        if ($page['theme']) {
            waRequest::setParam('theme', $page['theme']);
            $theme_template_path = $this->getTheme()->path.'/'.self::THEME_FILE;
            if (file_exists($theme_template_path)) {
                $this->setThemeTemplate(self::THEME_FILE);
            }
        }

        $this->view->assign([
            'page' => $page,
            'rendered_page_html' => (new siteBlockPage($page))->renderFrontend(),
        ]);
    }

    protected function setLastModified($page)
    {
        if (empty($page['update_datetime'])) {
            return;
        }

        $has_dynamic_content = false; // !!! TODO
        if ($has_dynamic_content) {
            $this->getResponse()->setLastModified(date("Y-m-d H:i:s"));
        } else {
            $this->getResponse()->setLastModified($page['update_datetime']);
        }
    }
}
