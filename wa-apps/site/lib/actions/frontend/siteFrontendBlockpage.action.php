<?php

class siteFrontendBlockpageAction extends waViewAction
{
    const THEME_FILE = 'blockpage.layout.html';

    public function execute()
    {
        $page = waRequest::param('page');
        if (!$page) {
            // this should not happen
            throw new waException('Block page not found', 500);
        }

        $this->setLastModified($page);

        $this->view->assign([
            'page' => $page,
            'rendered_page_html' => (new siteBlockPage($page))->renderFrontend(),
        ]);

        // Use theme defined by the blockpage
        if ($page['theme']) {
            waRequest::setParam('theme', $page['theme']);
            $theme_template_path = $this->getTheme()->path.'/'.self::THEME_FILE;
            if (file_exists($theme_template_path)) {
                $this->setThemeTemplate(self::THEME_FILE);
            }
        }
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
