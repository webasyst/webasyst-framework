<?php

class blogAkismetPlugin extends blogPlugin
{
    public function commentValidate($comment)
    {
        $result = null;

        if ( !$comment['contact_id'] && ($api_key = $this->getSettingValue('api_key')) && class_exists('Akismet')) {
            $url = wa()->getRouteUrl('blog', array(), true);
            $post_url = null;
            if (isset($comment['post_data'])) {
                $post_url = blogPost::getUrl($comment['post_data']);
                if (is_array($post_url)) {
                    $post_url = array_shift($post_url);
                }

            }
            $akismet = new Akismet($url, $api_key);

            $akismet->setCommentAuthor($comment['name']);
            $akismet->setCommentAuthorEmail($comment['email']);
            //$akismet->setCommentAuthorURL($comment['site']);
            $akismet->setCommentContent($comment['text']);
            if ($post_url) {
                $akismet->setPermalink($post_url);
            }

            if ($akismet->isCommentSpam()) {
                $result = array(
                	'text' => _wp('According to Akismet.com, your comment very much looks like spam, thus will not be published. Please rewrite your comment. Sorry for the inconvenience.'),
                );
            }
        }
        return $result;
    }

    public function addControls()
    {
        $this->addJs('js/akismet.js', true);
        $output = array();
        $string = _wp("mark as spam");
        $output['toolbar'] = <<<HTML
<script>
  $.wa.locale = $.extend($.wa.locale, {
    'mark as spam':'{$string}'
  });
</script>
HTML;
        $output['footer'] = $output['toolbar'];
        return $output;

    }
}