<?php
/**
 * Class responsible for keeping track of whole page templates.
 */
class siteBlockPageTemplates
{
    public static function getContactsPageTemplate()
    {
        return self::readFromFile('contacts.php');
    }

    public static function getSweetsPageTemplate()
    {
        return self::readFromFile('sweets.php');
    }

    public static function getFlowersPageTemplate()
    {
        return self::readFromFile('flowers.php');
    }

    public static function getMicrolandingPageTemplate()
    {
        return self::readFromFile('microlanding.php');
    }

    public static function getDeliveryPageTemplate()
    {
        return self::readFromFile('delivery.php');
    }

    public static function getBonusesPageTemplate()
    {
        return self::readFromFile('bonuses.php');
    }

    public static function getRefundPolicyPageTemplates()
    {
        return self::readFromFile('refundPolicy.php');
    }

    public static function getPartnersPageTemplate()
    {
        return self::readFromFile('partners.php');
    }

    public static function getCoffeeMachineArticlePageTemplate()
    {
        return self::readFromFile('coffeeMachineArticle.php');
    }

    public static function getBlackFridayPromoPageTemplate()
    {
        return self::readFromFile('blackFridayPromo.php');
    }

    public static function getUnderRepairPageTemplate()
    {
        return self::readFromFile('underRepair.php');
    }

    public static function getCollectionsPageTemplate()
    {
        return self::readFromFile('collections.php');
    }

    protected static function readFromFile(string $file)
    {
        $blocks = include(__DIR__.'/'.$file);
        if (!$blocks) {
            throw new waException('No page template data for '.$file);
        }
        if (count($blocks) == 1) {
            return siteBlockData::fromArray(reset($blocks));
        }

        $result = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        foreach ($blocks as $b) {
            $result->addChild(siteBlockData::fromArray($b));
        }
        return $result;
    }

    /** not used */
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

    /** not used */
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
