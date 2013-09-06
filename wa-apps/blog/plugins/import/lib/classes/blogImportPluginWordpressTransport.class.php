<?php
/**
 *
 * @link http://codex.wordpress.org/XML-RPC_WordPress_API
 * @link http://codex.wordpress.org/XML-RPC_Support
 * @version draft
 *
 *
 */
class blogImportPluginWordpressTransport extends blogImportPluginTransport
{
    private $version = 0;
    protected $xmlrpc_path = '/xmlrpc.php';

    protected function initOptions()
    {
        $this->options['url'] = array(
            'title'                  => _wp('Wordpress URL'),
            'description'            => _wp('Source blog URL'),
            'value'                  => 'http://',
            'settings_html_function' => waHtmlControl::INPUT,
        );
        $this->options['login'] = array(
            'title'                  => _wp('Login'),
            'value'                  => '',
            'description'            => _wp('Wordpress blog user login'),
            'settings_html_function' => waHtmlControl::INPUT,
        );
        $this->options['password'] = array(
            'title'                  => _wp('Password'),
            'value'                  => '',
            'description'            => _wp('Wordpress blog user password'),
            'settings_html_function' => waHtmlControl::PASSWORD,
        );
    }

    public function setup($runtime_settings = array())
    {

        parent::setup($runtime_settings);

        $options = $this->xmlrpc("wp.getOptions ", 1, $this->option('login'), $this->option('password'), 'software_version');
        if ($options && isset($options['software_version'])) {
            $this->version = $options['software_version']['value'];
            $this->log("WordPress version:\t{$this->version}", self::LOG_INFO);
        }
    }


    public function getPosts()
    {
        $this->log(__METHOD__, self::LOG_DEBUG);
        $ids = array();
        if (version_compare($this->version, '3.4', '>=')) {
            $filter = array(
                'number' => 9999,
            );
            $posts = $this->xmlrpc("wp.getPosts", 1, $this->option('login'), $this->option('password'), $filter, array('post_id', 'ID'));
            foreach ($posts as $post) {
                if (isset($post['post_id'])) {
                    $ids[] = $post['post_id'];
                } elseif (isset($post['ID'])) {
                    $ids[] = $post['ID'];
                }
            }
        } elseif (version_compare($this->version, '1.5.0', '>=')) {
            $posts = $this->xmlrpc('mt.getRecentPostTitles', 1, $this->option('login'), $this->option('password'), 9999);

            foreach ($posts as $post) {
                $ids[] = $post['postid'];
            }
        } else {
            throw new waException(_wp("WordPress version should be at least 1.5.0"));
        }
        $this->log(var_export($ids, true), self::LOG_DEBUG);

        return $ids;
    }

    private function stepImport()
    {
    }

    public function importPost($post_id)
    {
        $this->log(__METHOD__."({$post_id})", self::LOG_DEBUG);

        $post_data = $this->xmlrpc("metaWeblog.getPost", $post_id, $this->option('login'), $this->option('password'));

        $post = array();
        $datetime = $post_data['dateCreated'];
        $post['datetime'] = date("Y-m-d H:i:s", $datetime->timestamp);

        $post['title'] = (!empty($post_data['title'])) ? $post_data['title'] : '';
        if (!empty($post_data['mt_text_more'])) {
            $post['text'] = $this->applyFilter($post_data['description'].$post_data['mt_text_more'], 'the_content');
            $post['text_before_cut'] = $this->applyFilter($post_data['description'], 'the_content');
        } else {
            $post['text'] = $this->applyFilter($post_data['description'], 'the_content');
        }

        $post['comments_allowed'] = (($post_data['mt_allow_comments'] == 'open') || ($post_data['mt_allow_comments'] == 1)) ? 1 : 0;
        if (!empty($post_data['wp_slug'])) {
            $post['url'] = urldecode($post_data['wp_slug']);
        }
        $post['plugin'] = array();
        if (!empty($post_data['mt_keywords'])) {
            $post['plugin']['tag'] = $post_data['mt_keywords'];
        }

        switch ($post_data['post_status']) {
            case 'publish':
                $post['status'] = blogPostModel::STATUS_PUBLISHED;
                break;
            case 'future':
                $post['status'] = blogPostModel::STATUS_DEADLINE;
                break;
            case 'inherit':
                $this->log("Post with id [{$post_id}] skipped", self::LOG_NOTICE);
                $this->log("Skipped post raw data:\t".var_export($post_data, true), self::LOG_DEBUG);
                unset($post);
                break;
            default:
                $post['status'] = blogPostModel::STATUS_DRAFT;
                break;
        }

        try {
            if (!empty($post)) {

                if ($post = $this->insertPost($post)) {
                    $this->importComments($post_id, $post);
                }
            }
        } catch (waException $ex) {
            $this->log("Error while import post with id [{$post_id}]:\t".$ex->getMessage()."\nraw post:\t".var_export($post_data, true).(empty($post) ? '' : "\nformatted post:\t".var_export($post, true)), self::LOG_WARNING);
        }
        return empty($post['id']) ? null : $post['id'];
    }


    private function applyFilter($content, $filter)
    {
        $filters = array('the_content' => array());
        //        $filters['the_content'][] = 'wptexturize';//missed
        //        $filters['the_content'][] = 'convert_smilies';//missed
        $filters['the_content'][] = 'convert_chars';
        $filters['the_content'][] = 'wpautop';
        $filters['the_content'][] = 'shortcode_unautop';
        //        $filters['the_content'][] = 'prepend_attachment';//missed
        $filters['the_content'][] = 'userReplace';

        if (isset($filters[$filter])) {
            foreach ($filters[$filter] as $method) {
                if (is_callable($callback = array($this, $method))) {
                    $content = call_user_func($callback, $content);
                } else {
                    $this->log(__METHOD__." {$method} is not callable", self::LOG_WARNING);
                }
            }
        }
        return $content;
    }

    private function importComments($post_id, $post)
    {
        static $commentors = array();
        static $comment_model;
        if (version_compare($this->version, '2.7', '>=')) {

            try {
                if ($comments = $this->xmlrpc("wp.getComments", $post_id, $this->option('login'), $this->option('password'), array('post_id' => $post_id))) {
                    if (!isset($comment_model)) {
                        $comment_model = new blogCommentModel();
                    }
                    $comment_map = array();


                    // new comment to the top
                    $comments = array_reverse($comments);
                    $emails = array();
                    foreach ($comments as $comment) {
                        $email = trim(strtolower($comment['author_email']));
                        if ($email && !isset($commentors[$email])) {
                            $commentors[$email] = 0;
                            $emails[] = $email;
                        } else if (!isset($commentors[$email])) {
                            $commentors[$email] = 0;
                        }
                    }
                    $commentors = array_merge($commentors, $this->getContactByEmail($emails));
                    $comment_model->ping();

                    foreach ($comments as $key => $comment) {
                        $email = trim(strtolower($comment['author_email']));
                        $this->log('comment '.$key, self::LOG_DEBUG);
                        $datetime = $comment['date_created_gmt'];
                        $datetime = date("Y-m-d H:i:s", $datetime->timestamp);

                        $parent = 0;
                        if ($comment['parent'] && isset($comment_map[$comment['parent']])) {
                            $parent = $comment_map[$comment['parent']];
                        }

                        $contact_id = isset($commentors[$email]) ? $commentors[$email] : 0;


                        $comment_data = array(
                            'post_id'       => $post['id'],
                            'blog_id'       => $post['blog_id'],
                            'contact_id'    => $contact_id,
                            'text'          => html_entity_decode(strip_tags($comment['content']), ENT_NOQUOTES, 'utf-8'),
                            'datetime'      => $datetime,
                            'name'          => html_entity_decode(trim($comment['author']), ENT_NOQUOTES, 'utf-8'),
                            'email'         => $comment['author_email'],
                            'site'          => $comment['author_url'],
                            'ip'            => ip2long($comment['author_ip']),
                            'auth_provider' => $contact_id ? blogCommentModel::AUTH_USER : blogCommentModel::AUTH_GUEST,
                            'status'        => ($comment['status'] == 'approve') ? blogCommentModel::STATUS_PUBLISHED : blogCommentModel::STATUS_DELETED,
                        );


                        $comment_id = $comment_model->add($comment_data, $parent);

                        $comment_map[$comment['comment_id']] = $comment_id;
                    }
                    unset($comment_map);
                }
            } catch (waDbException $ex) {
                $message = '';
                if (!empty($comment)) {
                    $message .= "\nraw comment:\t".var_export($comment, true);
                }
                if (!empty($comment_data)) {
                    $message .= "\nformatted comment:\t".var_export($comment_data, true);
                }
                $this->log(__METHOD__.":\t".$ex->getMessage().$message, self::LOG_WARNING);
            } catch (waException $ex) {
                if ($ex->getCode() == 401) {
                    $this->log($ex->getMessage(), self::LOG_WARNING);
                } else {
                    throw $ex;
                }
            }
        }
    }

    private static function wpautop($pee)
    {
        $br = 1;
        if (trim($pee) === '') {
            return '';
        }
        $pee = $pee."\n"; // just to make things a little easier, pad the end
        $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        // Space things out a little
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
        $pee = preg_replace('!(<'.$allblocks.'[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</'.$allblocks.'>)!', "$1\n\n", $pee);
        $pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
        if (strpos($pee, '<object') !== false) {
            $pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
            $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
        }
        $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
        // make paragraphs, including one at the end
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        foreach ($pees as $tinkle) {
            $pee .= '<p>'.trim($tinkle, "\n")."</p>\n";
        }
        $pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
        $pee = preg_replace('!<p>\s*(</?'.$allblocks.'[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
        $pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
        $pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
        $pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
        $pee = preg_replace('!<p>\s*(</?'.$allblocks.'[^>]*>)!', "$1", $pee);
        $pee = preg_replace('!(</?'.$allblocks.'[^>]*>)\s*</p>!', "$1", $pee);
        if ($br) {
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', __CLASS__.'::_autop_newline_preservation_helper', $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
        }
        $pee = preg_replace('!(</?'.$allblocks.'[^>]*>)\s*<br />!', "$1", $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        if (strpos($pee, '<pre') !== false) {
            $pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', __CLASS__.'::clean_pre', $pee);
        }
        $pee = preg_replace("|\n</p>$|", '</p>', $pee);

        return $pee;
    }

    private static function _autop_newline_preservation_helper($matches)
    {
        return str_replace("\n", "<WPPreserveNewline />", $matches[0]);
    }

    private static function clean_pre($matches)
    {
        if (is_array($matches)) {
            $text = $matches[1].$matches[2]."</pre>";
        } else {
            $text = $matches;
        }

        $text = str_replace('<br />', '', $text);
        $text = str_replace('<p>', "\n", $text);
        $text = str_replace('</p>', '', $text);

        return $text;
    }

    /**
     * Converts a number of characters from a string.
     *
     * Metadata tags <<title>> and <<category>> are removed, <<br>> and <<hr>> are
     * converted into correct XHTML and Unicode characters are converted to the
     * valid range.
     *
     * @since 0.71
     *
     * @param string $content String of characters to be converted.
     * @return string Converted string.
     */
    private static function convert_chars($content)
    {

        // Translation of invalid Unicode references range to valid range
        $wp_htmltranswinuni = array(
            '&#128;' => '&#8364;', // the Euro sign
            '&#129;' => '',
            '&#130;' => '&#8218;', // these are Windows CP1252 specific characters
            '&#131;' => '&#402;', // they would look weird on non-Windows browsers
            '&#132;' => '&#8222;',
            '&#133;' => '&#8230;',
            '&#134;' => '&#8224;',
            '&#135;' => '&#8225;',
            '&#136;' => '&#710;',
            '&#137;' => '&#8240;',
            '&#138;' => '&#352;',
            '&#139;' => '&#8249;',
            '&#140;' => '&#338;',
            '&#141;' => '',
            '&#142;' => '&#382;',
            '&#143;' => '',
            '&#144;' => '',
            '&#145;' => '&#8216;',
            '&#146;' => '&#8217;',
            '&#147;' => '&#8220;',
            '&#148;' => '&#8221;',
            '&#149;' => '&#8226;',
            '&#150;' => '&#8211;',
            '&#151;' => '&#8212;',
            '&#152;' => '&#732;',
            '&#153;' => '&#8482;',
            '&#154;' => '&#353;',
            '&#155;' => '&#8250;',
            '&#156;' => '&#339;',
            '&#157;' => '',
            '&#158;' => '',
            '&#159;' => '&#376;'
        );

        // Remove metadata tags
        $content = preg_replace('/<title>(.+?)<\/title>/', '', $content);
        $content = preg_replace('/<category>(.+?)<\/category>/', '', $content);

        // Converts lone & characters into &#38; (a.k.a. &amp;)
        $content = preg_replace('/&([^#])(?![a-z1-4]{1,8};)/i', '&#038;$1', $content);

        // Fix Word pasting
        $content = strtr($content, $wp_htmltranswinuni);

        // Just a little XHTML help
        $content = str_replace('<br>', '<br />', $content);
        $content = str_replace('<hr>', '<hr />', $content);

        return $content;
    }

    private static function shortcode_unautop($pee)
    {
        global $shortcode_tags;

        if (empty($shortcode_tags) || !is_array($shortcode_tags)) {
            return $pee;
        }

        $tagregexp = join('|', array_map('preg_quote', array_keys($shortcode_tags)));

        $pattern =
            '/'
            .'<p>' // Opening paragraph
            .'\\s*+' // Optional leading whitespace
            .'(' // 1: The shortcode
            .'\\[' // Opening bracket
            ."($tagregexp)" // 2: Shortcode name
            .'\\b' // Word boundary
            // Unroll the loop: Inside the opening shortcode tag
            .'[^\\]\\/]*' // Not a closing bracket or forward slash
            .'(?:'
            .'\\/(?!\\])' // A forward slash not followed by a closing bracket
            .'[^\\]\\/]*' // Not a closing bracket or forward slash
            .')*?'
            .'(?:'
            .'\\/\\]' // Self closing tag and closing bracket
            .'|'
            .'\\]' // Closing bracket
            .'(?:' // Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .'[^\\[]*+' // Not an opening bracket
            .'(?:'
            .'\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .'[^\\[]*+' // Not an opening bracket
            .')*+'
            .'\\[\\/\\2\\]' // Closing shortcode tag
            .')?'
            .')'
            .')'
            .'\\s*+' // optional trailing whitespace
            .'<\\/p>' // closing paragraph
            .'/s';

        return preg_replace($pattern, '$1', $pee);
    }
}
//EOF
