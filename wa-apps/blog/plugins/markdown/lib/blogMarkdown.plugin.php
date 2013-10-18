<?php

class blogMarkdownPlugin extends blogPlugin
{
    public function backendPostEdit($post) {
        $text_markdown = isset($post['text_markdown']) ? $post['text_markdown'] : '';
        return array(
            'editor_tab' => 
                '<a id="markdown" href="javascript:void(0);">'._wp('Markdown').'</a>'.
                '<textarea id="post_text_markdown" name="plugin[markdown]" style="display:none;">'.$text_markdown.'</textarea>'.
                '<span style="display:none;" id="markdown_plugin_text_no_markup_yet">'._wp('This post was not edited in the Markdown mode yet (<a href="http://en.wikipedia.org/wiki/Markdown" target="_blank">what is Markdown?</a>). If you want to continue editing in Markdown instead of HTML, create a Markdown version and continue editing using the Markdown syntax. Current HTML version will be automatically re-compiled from the Markdown content during the next post save.').'</span>'.
                '<span style="display:none;" id="markdown_plugin_text_generate">'._wp('Create Markdown version').'</span>'.
                '<span style="display:none;" id="markdown_plugin_text_new_version">'._wp('Current post content HTML version is newer than the current Markdown version (below). If you save your Markdown content now, newest HTML content will be erased and compiled from the current Markdown version.').'</span>'.
                '<span style="display:none;" id="markdown_plugin_text_update">'._wp('Recreate Markdown from the latest HTML version').'</span>'.
                '<span style="display:none;" id="markdown_plugin_text_override">'._wp('Override current Markdown content and update it from the latest HTML version?').'</span>'
        );
    }
    
    public function backendAssets() {
        $this->addJs('js/backend.js?'.wa()->getVersion());
        $this->addJs('js/markdown.js?'.wa()->getVersion());
        $this->addJs('js/to-markdown.js?'.wa()->getVersion());
    }
    
    public function postSave($post)
    {
        $this->updateMarkdownText($post);
    }
    
    public function postPublish($post)
    {
        $this->updateMarkdownText($post);
    }
    
    public function postSchedule($post)
    {
        $this->updateMarkdownText($post);
    }
    
    public function updateMarkdownText($post)
    {
        $post_id = $post['id'];
        $text = null;
        if (isset($post['plugin']) && isset($post['plugin'][$this->id]) && $post['plugin'][$this->id]) {
            $text = trim($post['plugin'][$this->id]);
        }
        $post_model = new blogPostModel();
        $post_model->updateById($post_id, array('text_markdown' => $text));        
    }
    
}