<?php
/**
 * UI 2.0 layout with list of WA apps visible in the header.
 * Used for most of Site app's screens - basically all except WYSIWYG block pag editor.
 *
 * When page is requested via XHR, layout is empty, only main page content is rendered.
 *
 * When requested in a regular way (no XHR), full layout is rendered with <!DOCTYPE> and stuff.
 */
class siteBackendLayout extends waLayout
{
    public $options;

    public function __construct($options = [])
    {
        if (wa()->whichUI() == '1.3') {
            wa()->getResponse()->redirect(wa()->getAppUrl('site'));
        }
        $this->options = $options;
        parent::__construct();
    }

    public function execute()
    {
        // Page content only? No DOCTYPE, no wa header, just the main content
        if (waRequest::isXMLHttpRequest()) {
            $this->template = 'string:{$content}';
            return;
        }

        $custom_header_type = null;
        if (!empty($this->options['hide_wa_app_icons'])) {
            $custom_header_type = ifset($this->options, 'custom_header_type', 'block_editor');
        }

        $this->view->assign([
            'hide_wa_app_icons' => !empty($this->options['hide_wa_app_icons']),
            'custom_header_type' => $custom_header_type,
            'rights' =>  [
                'admin'  => $this->getUser()->isAdmin('site'),
            ],
        ]);
    }

    public function display()
    {
        $this->execute();
        $template = $this->getTemplate();
        if (!$template || $template === 'string:{$content}') {
            $html = ifset($this->blocks, 'content', '');
        } else {
            $this->view->assign($this->blocks);
            $this->view->cache(false);
            $html = $this->view->fetch($this->getTemplate());
        }
        wa()->getResponse()->sendHeaders();
        echo $html;
    }
}
