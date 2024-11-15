<?php
/**
 * Create new block page (possibly from template)
 */
class siteMapCreateBlockPageController extends waJsonController
{
    public function execute()
    {
        $domain_id = waRequest::request('domain_id', null, 'int');
        $template_id = waRequest::request('template_id');

        $domains = siteHelper::getDomains(true);
        if (!$domain_id || empty($domains[$domain_id])) {
            throw new waException('Domain not found', 404);
        }
        $domain = $domains[$domain_id];

        if ($template_id) {
            throw new waException('Create from template is not implemented yet', 404); // !!!
        } else {
            // Empty page
            $blockpage_model = new siteBlockpageModel();
            $page_id = $blockpage_model->createEmptyUnpublishedPage($domain_id);
        }

        $this->response = [
            'id' => $page_id,
        ];

    }
}
