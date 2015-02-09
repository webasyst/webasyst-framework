<?php

class blogPost
{
    static function getUrl($post, $type = 'post')
    {
        if ($type == 'post' && !empty($post['album_id']) && $post['album_link_type'] == 'photos') {
            wa('photos');
            if (empty($post['album']['full_url'])) {
                $album_full_url = photosCollection::frontendAlbumHashToUrl('album/'.$post['album_id']);
            } else {
                $album_full_url = $post['album']['full_url'];
            }
            $url = photosFrontendAlbum::getLink($album_full_url);
            if (wa()->getEnv() == 'backend') {
                return array($url);
            } else {
                return $url;
            }
        }

        static $blog_urls = array();

        $params = array();
        $fields = array('blog_url', 'year', 'month', 'day');
        foreach ($fields as $field) {
            if (isset($post[$field])) {
                $params[$field] = $post[$field];
            }
        }
        if (isset($post['id']) && $post['id'] && isset($post['url']) && $post['url']) {
            $params['post_url'] = $post['url'];
        } elseif ($type != 'timeline') {
            $params['post_url'] = '%post_url%';
        }

        $blog_id = null;
        if ($type != 'author') {
            if (isset($post['datetime']) && $post['datetime'] && $time = date_parse($post['datetime'])) {
                $params['post_year'] = sprintf('%04d', $time['year']);
                $params['post_month'] = sprintf('%02d', $time['month']);
                $params['post_day'] = sprintf('%02d', $time['day']);
            } elseif ($type != 'timeline') {
                $params['post_year'] = '%year%';
                $params['post_month'] = '%month%';
                $params['post_day'] = '%day%';
            }
            if (!isset($params['blog_url']) && isset($post['blog_id'])) {
                $blog_id = $post['blog_id'];

                if (!isset($blog_urls[$blog_id])) {
                    $blog_urls[$blog_id] = false;
                    $blog_model = new blogBlogModel();
                    if ($blog_data = $blog_model->getById($blog_id)) {
                        if ($blog_data['status'] == blogBlogModel::STATUS_PUBLIC) {
                            if (strlen($blog_data['url'])) {
                                $blog_urls[$blog_id] = $blog_data['url'];
                            } else {
                                $blog_urls[$blog_id] = $blog_id;
                            }
                        }
                    }
                }
                $params['blog_url'] = $blog_urls[$blog_id];
            } elseif (isset($params['blog_url']) && isset($post['blog_id'])) {
                $blog_id = $post['blog_id'];
            }
        }
        $route = false;
        if (!isset($params['blog_url']) || ($params['blog_url'] !== false)) {
            switch ($type) {
                case 'comment':
                    $route = 'blog/frontend/comment';
                    break;
                case 'timeline':
                    $route = 'blog/frontend';
                    break;
                case 'author':
                    if ($params['contact_id'] = $post['contact_id']) {
                        $route = 'blog/frontend';
                    }
                    break;
                case 'post':
                default:
                    $route = 'blog/frontend/post';
                    break;
            }
        }
        return $route ? blogHelper::getUrl($blog_id, $route, $params) : array();
    }

    static function move($blog_id, $move_blog_id)
    {
        if ($blog_id != $move_blog_id) {
            $post_model = new blogPostModel();
            $post_model->updateByField('blog_id', $blog_id, array('blog_id' => $move_blog_id));

            $comment_model = new blogCommentModel();
            $comment_model->updateByField('blog_id', $blog_id, array('blog_id' => $move_blog_id));

            $blog_model = new blogBlogModel();
            $blog_model->recalculate(array($blog_id, $move_blog_id));
        }
    }

    /**
     * @static
     * @param Exception $ex
     * @param $post
     * @return string
     */
    public static function handleTemplateException($ex, $post)
    {

        $output = '';
        if (wa()->getConfig()->isDebug()) {
            $pattern = '/Syntax\s+Error\s+in\s+template\s+.*\s+on\s+line\s+(\d+)/';
            $message = $ex->getMessage();
            if (preg_match($pattern, $message, $matches)) {
                $lines = preg_split("/\n/", $post['text']);
                $line = $matches[1];
                $context_radius = 5;
                $lines = array_slice($lines, $line - $context_radius, 2 * $context_radius - 1, true);
                $output .= '<div class="error">'.htmlentities($message, ENT_QUOTES, 'utf-8');
                $output .= '<pre class="error">';
                $template = "%3s%0".ceil(log10($line) + 1)."d\t%s";
                foreach ($lines as $n => $content) {
                    $output .= sprintf($template, (($n + 1) == $line) ? '>>' : '', $n, htmlentities($content, ENT_QUOTES, 'utf-8'));
                }
                $output .= "</pre></div>";

            } else {
                $output = '<pre class="error">'.htmlentities($ex->getMessage(), ENT_QUOTES, 'utf-8')."</pre>";
            }
        } else {
            waLog::log($ex);
            $output .= '<div class="error">'._w('Syntax error at post template').'</div>';
        }
        return $output;

    }

    /**
     * Closing all opened tags
     * Simple algorithm - closing without open tags' order
     * @param string $content
     * @param array $ignored_tags
     * @return string
     */
    public static function closeTags($content, $ignored_tags = array('br', 'hr', 'img'))
    {
        $pos = 0;
        $open_tags = array();

        $count = 0;
        while (($pos = strpos($content, '<', $pos)) !== false) {
            if (preg_match("|^<(/?)([a-z\d]+)\b[^>]*>|i", substr($content, $pos), $match)) {
                $tag = strtolower($match[2]);
                if (in_array($tag, $ignored_tags) === false) {
                    if (empty($match[1])) {
                        if (isset($open_tags[$tag])) {
                            $open_tags[$tag] += 1;
                        } else {
                            $open_tags[$tag] = 1;
                        }
                    } else if (isset($match[1]) && $match[1] == '/') {
                        if (isset($open_tags[$tag])) {
                            $open_tags[$tag] -= 1;
                        }
                    }
                }
                $pos += strlen($match[0]);
            } else {
                $pos += 1;
            }
            $count++;
        }
        // close tags
        foreach ($open_tags as $tag => $cnt) {
            $content .= str_repeat("</$tag>", $cnt);
        }

        return $content;
    }

}