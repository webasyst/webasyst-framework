<?php

class blogPreviewLayout extends blogFrontendLayout
{

    /**
     * This need for render preview
     * @param bool $clear_assign
     * @throws SmartyException
     */
    public function display($clear_assign = false)
    {
        $this->execute();
        $this->view->assign($this->blocks);

        if ((wa()->getEnv() == 'frontend') && waRequest::param('theme_mobile') &&
            (waRequest::param('theme') != waRequest::param('theme_mobile'))) {
            wa()->getResponse()->addHeader('Vary', 'User-Agent');
        }
        wa()->getResponse()->sendHeaders();
        $this->view->cache(false);
        if ($this->view->autoescape() && $this->view instanceof waSmarty3View) {
            $this->view->smarty->loadFilter('pre', 'content_nofilter');
        }

        $result = $this->view->fetch($this->getTemplate());

        //Because the design themes escaped the title
        $result = str_replace('%replace-with-real-post-title%', '<span class="replace-with-real-post-title"></span>', $result);
        echo $result;
    }
}
