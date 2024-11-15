<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteFooterBlockType extends siteBlockType
{

    public function __construct(array $options=[])
    {
        $options['type'] = 'site.Footer';
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $footer_top = (new siteFooterTopBlockType(['columns' => 4]))->getExampleBlockData();
        $footer_bottom = (new siteFooterBottomBlockType(['columns' => 3]))->getExampleBlockData();
        //$result->data['elements'] = $this->elements;
        $vseq->addChild($footer_top);
        $vseq->addChild($footer_bottom);

        $result = $this->getEmptyBlockData();
        $result->addChild($vseq, '');

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
        ]);
    }

    public function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Footer'),
            'sections' => [
                ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
