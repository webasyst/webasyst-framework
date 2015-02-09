<?php

/**
 * Return plain-text version of a message from HTML version.
 */
class mailerCampaignsGetPlainTextController extends waJsonController
{
    public function execute()
    {
        $html = waRequest::post('html');
        $this->response = mailerHtml2text::convert($html);
    }
}

