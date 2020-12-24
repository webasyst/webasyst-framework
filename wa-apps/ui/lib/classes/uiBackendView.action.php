<?php
/**
 * All backend view actions should inherit from this
 * to enable automatic layout substitution unless loaded via AJAX
 */
abstract class uiBackendViewAction extends waViewAction
{
    public function __construct($params = null)
    {
        parent::__construct($params);

        if (!waRequest::isXMLHttpRequest()) {
            $this->setLayout(new uiDefaultLayout());
        }
    }
}
