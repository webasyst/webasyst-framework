<?php
/**
 * HTML for a dropdown list to select element to add inside a block.
 */
class siteEditorAddElementsListAction extends siteEditorAddBlockDialogAction
{
    protected function getLibraryContents($parent_block)
    {
        $complex_param = ''; //type String = '' | 'only_columns' | 'with_row' | 'no_complex'
        $library = new siteBlockpageLibrary();

        if (!empty($parent_block)) {
            //need for show special elements in dropdown
            if (!empty($parent_block['data']) && !empty(json_decode($parent_block['data'])->is_complex)) {
                $complex_param = json_decode($parent_block['data'])->is_complex;
            }
        }

        return $library->getAllElements($complex_param);
    }
}
