<?php
/**
 * Class responsible for keeping track of whole page templates.
 */
class siteBlockpageTemplates
{
    public function createFromDefaultTemplate($domain_id)
    {
        $blockpage_model = new siteBlockpageModel();
        $page_id = $blockpage_model->createEmptyUnpublishedPage($domain_id);
        $blockpage_model->updateById($page_id, [
            'name' => _w('Homepage'),
        ]);

        $template = $this->getDefaultPageTemplateData();
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        foreach($template as $block_data) {
            $blockpage_blocks_model->addToParent($block_data, $page_id);
        }

        return $page_id;
    }

    protected function getDefaultPageTemplateData()
    {
        return [
            (new siteHeaderBlockType())->getExampleBlockData(),
            (new siteHeadingBlockType())->getExampleBlockData(),
            (new siteParagraphBlockType())->getExampleBlockData(),
            (new siteFooterBlockType())->getExampleBlockData(),
        ];
    }
}
