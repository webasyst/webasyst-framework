<?php
/**
 * @link http://www.livejournal.com/doc/server/ljp.csp.xml-rpc.protocol.html
 *
 */

class blogImportPluginLjTransport extends blogImportPluginTransport
{
    protected $xmlrpc_url = 'http://www.livejournal.com';
    protected $xmlrpc_path = '/interface/xmlrpc';

    private $post_model;
    private $challenge;
    private $auth_response;
    private $lastsync;

    public function setup($runtime_settings = array())
    {
        parent::setup($runtime_settings);
        $this->lastsync = date('Y-m-d H:i:s', 0);
        $this->post_model = new blogPostModel();
        $this->get_challenge();
    }


    protected function initOptions()
    {
        $this->options['lj_user'] = array(
            'title'                  => _wp('LiveJournal user'),
            'value'                  => '',
            'settings_html_function' => waHtmlControl::INPUT,
        );
        $this->options['lj_password'] = array(
            'title'                  => _wp('Password'),
            'value'                  => '',
            'settings_html_function' => waHtmlControl::PASSWORD,
        );
    }

    public function getPosts()
    {
        $sync_item_times = array();
        $lastsync = date('Y-m-d H:i:s', 0);
        do {
            $synclist = $this->lj_query('syncitems', array('ver' => 1, 'lastsync' => $lastsync));

            // Keep track of if we've downloaded everything
            $total = $synclist['total'];
            $count = $synclist['count'];
            $this->log("RAW DATA ".var_export($synclist['syncitems'], true), self::LOG_DEBUG);

            foreach ($synclist['syncitems'] as $event) {
                if (substr($event['item'], 0, 2) == 'L-') {
                    $sync_item_times[] = intval(substr($event['item'], 2));
                    if ($event['time'] > $lastsync) {
                        $lastsync = $event['time'];
                    }
                }
            }
        } while ($total > $count);
        return $sync_item_times;
    }

    public function importPost($post_id)
    {
        try {
            $post = array();
            $options = array(
                'ver'         => 1,
                'selecttype'  => 'one',
                'lineendings' => 'pc',
                'itemid'      => $post_id,
            );

            $itemlist = $this->lj_query('getevents', $options);

            if ($post_data = array_shift($itemlist['events'])) {
                // Clean up content

                $post['datetime'] = date("Y-m-d H:i:s", strtotime($post_data['eventtime']));
                $post['text'] = self::parse_lj_text((string)(is_object($property = $post_data['event']) ? $property->scalar : $property));

                // Check if comments are closed on this post
                $post['comments_allowed'] = !empty($post_data['props']['opt_nocomments']) ? 0 : 1;
                $post['title'] = trim((string)(is_object($property = $post_data['subject']) ? $property->scalar : $property));

                $post['title'] = $post['title'] ? self::translate_lj_user($post['title']) : '';
                $post['title'] = strip_tags($post['title']);

                $post['plugin'] = array();
                if (!empty($post_data['props']['taglist'])) {
                    $post['plugin']['tag'] = (string)(is_object($property = $post_data['props']['taglist']) ? $property->scalar : $property);
                }

                $post['status'] = blogPostModel::STATUS_PUBLISHED;

                $this->insertPost($post);
            }
        } catch (waException $ex) {
            $this->log("Error while import post with id [{$post_id}]:\t".$ex->getMessage().(empty($post_data) ? '' : "\nraw post:\t".var_export($post_data, true)).(empty($post) ? '' : "\nformatted post:\t".var_export($post, true)), self::LOG_WARNING);
        }
        return empty($post['id']) ? null : $post['id'];
    }

    private static function translate_lj_user($str)
    {
        return preg_replace('|<lj\s+user\s*=\s*["\']([\w-]+)["\']>|', '<a href="http://$1.livejournal.com/" class="lj-user">$1</a>', $str);
    }

    private static function parse_lj_text($text)
    {
        $text = preg_replace_callback('|<(/?[A-Z]+)|', create_function('$match', 'return "<" . strtolower( $match[1] );'), $text);

        // XHTMLize some tags
        $text = str_replace('<br>', '<br />', $text);
        $text = str_replace('<hr>', '<hr />', $text);

        // lj-cut ==>  <!--more-->
        $text = preg_replace('|<lj-cut text="([^"]*)">|is', '<!--more $1-->', $text);
        $text = str_replace(array('<lj-cut>', '</lj-cut>'), array('<!--more-->', ''), $text);
        $first = strpos($text, '<!--more');
        $text = substr($text, 0, $first + 1).preg_replace('|<!--more(.*)?-->|sUi', '', substr($text, $first + 1));

        // lj-user ==>  a href
        return self::translate_lj_user($text);
    }

    private function lj_query()
    {
        $params = array();
        if ($this->get_challenge()) {
            $params = array('username'       => $this->option('lj_user'),
                            'auth_method'    => 'challenge',
                            'auth_challenge' => $this->challenge,
                            'auth_response'  => $this->auth_response
            );
            $this->challenge = null;
        } else {
            waLog::log(_wp('LiveJournal does not appear to be responding right now. Please try again later.'));
        }

        $args = func_get_args();
        $method = array_shift($args);
        if (isset($args[0])) {
            $params = array_merge($params, $args[0]);
        }
        return $this->xmlrpc("LJ.XMLRPC.{$method}", $params);
    }

    private function get_challenge()
    {
        if (!$this->challenge) {
            $challenge = $this->xmlrpc('LJ.XMLRPC.getchallenge');
            if (empty($challenge['challenge'])) {

                throw new waException(_wp('LiveJournal does not appear to be responding right now. Please try again later.'));
            } else {
                $this->challenge = $challenge['challenge'];
                $this->auth_response = md5($this->challenge.md5($this->option('lj_password')));
            }
        }
        return $this->challenge;
    }
}
