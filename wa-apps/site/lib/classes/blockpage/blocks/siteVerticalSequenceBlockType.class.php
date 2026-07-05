<?php
/**
 * Vertical sequence of any number of blocks.
 * Used as the backbone of the whole page (root block that contains everything)
 * as well as inside other blocks e.g. two columns layout.
 */
class siteVerticalSequenceBlockType extends siteBlockType
{
    protected function getDefaultRenderTemplatePath(bool $is_backend)
    {
        if ($is_backend) {
            return parent::getDefaultRenderTemplatePath($is_backend);
        }
        $filename = dirname(__FILE__).'/templates/VerticalSequence.front.html';
        return $filename;
    }

    /** Override this in subclass to use in default prerender() implementation */
    protected function getDefaultPrerenderTemplatePath(bool $is_backend)
    {
        if ($is_backend) {
            return dirname(__FILE__).'/templates/'.substr(get_class($this), 4, -9).'.prerender.html';
        } else {
            return dirname(__FILE__).'/templates/'.substr(get_class($this), 4, -9).'.script.html';
        }
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend, $tmpl_vars), 'array_merge', []),
        ]);
    }
}