<?php
class blogConfig extends waAppConfig
{

    public function onInit()
    {
        $wa = wa();
        $id = $wa->getUser()->getId();
        if ($id && ($wa->getApp() == 'blog') && ($wa->getEnv() == 'backend')) {
            $this->setCount($this->onCount(false));
            blogActivity::setUserActivity($id,false);
        }

    }

    public function getColors()
    {
        return (isset($this->options['colors']) ? $this->options['colors'] : array());
    }

    public function getIcons()
    {
        return (isset($this->options['icons']) ? $this->options['icons'] : array());
    }


    public function getRouting($route = array(), $dispatch = false)
    {
        static $routes_cache = array();
        $key = md5(serialize($route));
        if (!isset($routes_cache[$key])) {
            $routes = parent::getRouting();
            if ($routes) {
                $blog_id = isset($route['blog_url_type'])?$route['blog_url_type']:0;
                switch(intval($blog_id)) {
                    case -1:{$rules = $routes[2];break;}
                    case 0:{$rules = $routes[0];break;}
                    default: {$rules = $routes[1];break;}
                }
                $post_url = isset($route['post_url_type'])?$route['post_url_type']:0;
                $pattern = false;
                $year =  '((19|20)[\d]{2})';
                $month = '([0]?\d|1[0-2])';
                $day = '([0-2]\d|3[0-1]|\d)';
                switch($post_url) {
                    case 3: $pattern = '<post_year:'.$year.'>/<post_month:'.$month.'>/<post_day:'.$day.'>/<post_url>'; break;
                    case 2: $pattern = '<post_year:'.$year.'>/<post_month:'.$month.'>/<post_url>'; break;
                    case 1: $pattern = '<post_year:'.$year.'>/<post_url>'; break;
                    case 0:
                    default:
                        break;
                }
                /**
                 * Extend routing via plugin routes
                 * @event routing
                 * @param array $route
                 * @return array route
                 */
                $result = wa()->event(array('blog', 'routing'), $route);
                $plugin_routes = array();
                foreach ($result as $rs) {
                    $plugin_routes = array_merge($plugin_routes, $rs);
                }
                if ($plugin_routes) {
                    $rules = array_merge($plugin_routes, $rules);
                }
                if ($pattern) {
                    $new_rules = array();
                    foreach ($rules as $rule_id =>$rule) {
                        if (strpos($rule_id, '<post_url>') === false) {
                            $new_rules[$rule_id] = $rule;
                        } else {

                            $rule_id = str_replace('<post_url>', $pattern, $rule_id);
                            $new_rules[$rule_id] = $rule;
                        }
                    }
                    $rules = $new_rules;
                }
                $routes_cache[$key] = $rules;
            } else {
                $routes_cache[$key] = array();
            }
        }
        return $routes_cache[$key];
    }

    public function onCount()
    {
        $full = !func_get_args();

        $app = $this->getApplication();

        $user = waSystem::getInstance()->getUser();
        $user_id = $user->getId();
        $type = explode(':',$user->getSettings($app, 'type_items_count'));
        $type = array_filter(array_map('trim',$type),'strlen');
        if (!$type) {
            $type = array('posts','comments_to_my_post','overdue');
        }

        $activity_datetime = blogActivity::getUserActivity($user_id, false);

        $blogs = array_keys(blogHelper::getAvailable(false));

        $counter = array();

        $post_model = new blogPostModel();
        if (in_array('posts',$type) && $full && $blogs) {
            $post_new_count = $post_model->getAddedPostCount($activity_datetime, $blogs);
            $post_new_count = array_sum($post_new_count);
            $counter['posts'] = $post_new_count;
        } else {
            $counter['posts'] = false;
        }

        if (in_array('comments',$type) && $full && $blogs) {
            $comment_model = new blogCommentModel();
            $counter['comments'] = $comment_model->getCount($blogs, null, $activity_datetime, 0);
        } else {
            $counter['comments'] = false;
        }

        if (in_array('comments_to_my_post',$type) && $full && $blogs) {
            $comment_model = new blogCommentModel();
            $counter['comments_to_my_post'] = $comment_model->getCount($blogs, null, $activity_datetime, 0, $user_id);
        } else {
            $counter['comments_to_my_post'] = false;
        }

        if (in_array('overdue',$type) && $blogs) {
            if (!isset($post_model)) {
                $post_model = new blogPostModel();
            }

            $where = "status = '".blogPostModel::STATUS_DEADLINE."'";

            $where .= " AND blog_id IN (".implode(', ',$blogs).")";
            $where .= " AND contact_id = {$user_id}";
            $where .= " AND datetime <= '".waDateTime::date("Y-m-d")."'";
            $count_overdue = $post_model->select("count(id)")->where($where)->fetchField();
            $counter['overdue'] = ($count_overdue) ? $count_overdue : 0;
        } else {
            $counter['overdue'] = false;
        }

        $count = array_sum($counter);
        $url = $this->getBackendUrl(true).$this->application.'/';
        if($count) {
            switch($count) {
                case $counter['comments']:
                case $counter['comments_to_my_post']: {
                    $url .= '?module=comments';
                    break;
                }
                case $counter['overdue']: {
                    $url .= '?action=calendar';
                    break;
                }

            }
        }
        //debug
        //$counter['type'] = $type;
        //$counter['activity_datetime'] = $activity_datetime;
        //$counter['current_datetime'] = date("Y-m-d H:i:s",time());
        //waLog::log('$counter = '.var_export($counter,true),"blog-counter-{$user_id}.log");
        return array( 'count' => ($count == 0 ? null : $count),'url'=>$url);
    }

    public function getCronJob($name = null)
    {
        static $tasks;
        if (!isset($tasks)) {
            $tasks = array();
            $path = $this->getAppConfigPath('cron');
            if (file_exists($path)) {
                $tasks = include($path);
            } else {
                $tasks = array();
            }
        }
        return $name?(isset($tasks[$name])?$tasks[$name]:null):$tasks;
    }

    public function checkRights($module, $action)
    {
        if ($module == 'pages' && $action != 'uploadimage') {
            return wa()->getUser()->getRights($this->application, blogRightConfig::RIGHT_PAGES);
        } else {
            return true;
        }
    }

    public function explainLogs($logs)
    {
        $logs = parent::explainLogs($logs);
        $app_url = wa()->getConfig()->getBackendUrl(true).$this->application.'/';

        $post_ids = array();
        $comment_ids = array();
        foreach ($logs as $l_id => $l) {
            if (in_array($l['action'], array('page_add', 'page_edit', 'page_move')) && isset($l['params_html'])) {
                $logs[$l_id]['params_html'] = str_replace('#/pages/', '?module=pages#/', $l['params_html']);
            } else if ($l['action'] == 'post_edit' && version_compare(wa('webasyst')->getVersion(), '1.4.0.40888') >= 0) {
                // Removal of log records in activity is only supported since 1.4.0.40888,
                // but we don't want to raise requirements yet, so have to check for version here.
                // !!! TODO: should probably remove the check later and update requirements.php
                $logs[$l_id] = null;
            } else if (in_array($l['action'], array('post_edit', 'post_publish', 'post_unpublish')) && $l['params']) {
                $post_ids[$l['params']] = 1;
            } else if (in_array($l['action'], array('comment_add', 'comment_delete', 'comment_restore')) && $l['params']) {
                $comment_ids[$l['params']] = 1;
            }
        }
        if ($comment_ids) {
            $comment_model = new blogCommentModel();
            $comments = $comment_model->getById(array_keys($comment_ids));
            foreach($comments as $c) {
                $post_ids[$c['post_id']] = 1;
            }
        }
        if ($post_ids) {
            $post_model = new blogPostModel();
            $posts = $post_model->getById(array_keys($post_ids));
        }
        foreach ($logs as $l_id => $l) {
            if (!$l) {
                continue;
            }

            // Link to blog post in question
            $p = $c = null;
            if (in_array($l['action'], array('post_edit', 'post_publish', 'post_unpublish')) && isset($posts[$l['params']])) {
                $p = $posts[$l['params']];
            } else if (in_array($l['action'], array('comment_add', 'comment_delete', 'comment_restore')) && isset($comments[$l['params']])) {
                $c = $comments[$l['params']];
                if (isset($posts[$c['post_id']])) {
                    $p = $posts[$c['post_id']];
                }
            }
            if (!empty($p)) {
                if ($p['status'] == blogPostModel::STATUS_PUBLISHED) {
                    $url = $app_url.'?module=post&id='.$p['id'];
                } else {
                    $url = $app_url.'?module=post&action=edit&id='.$p['id'];
                }
                $logs[$l_id]['params_html'] = '<div class="activity-target"><a href="'.$url.'">'.htmlspecialchars($p['title']).'</a></div>';
            }
            if (!empty($c)) {
                $logs[$l_id]['params_html'] .= '<div class="activity-body"><p'.($c['status'] == 'deleted' ? ' class="strike gray"' : '').'>'.nl2br(htmlspecialchars(mb_substr($c['text'], 0, 512))).'</p></div>';
            }
        }
        return $logs;
    }
}

