<?php

class blogFrontController extends waFrontController
{
    public function execute($plugin = null, $module = null, $action = null, $default = false)
    {
        if (!$plugin && $module == 'frontend') {
            try {
                if (!waRequest::param('page_id')) {
                    $request_url = parse_url($this->system->getRootUrl().$this->system->getConfig()->getRequestUrl());
                    if (isset($request_url['path']) && $request_url['path'] && (substr($request_url['path'], -1) != '/')) {
                        $request_url['path'] .= '/';
                        $this->system->getResponse()->redirect(implode('', $request_url), 301);
                    }
                }

                #parse request URL
                $params = waRequest::param();
                #determine blog ID which are dependent on routing settings
                $blog_model = new blogBlogModel();
                $blogs = array();
                $params['blog_url_type'] = waRequest::param('blog_url_type', 0, waRequest::TYPE_INT);
                if (!($title = waRequest::param('title'))) {
                    $title = wa()->accountName();
                }

                $blog_url = waRequest::param('blog_url', '', waRequest::TYPE_STRING_TRIM);
                $main_page = false;
                if ($params['blog_url_type'] > 0) {
                    if ($blog = $blog_model->getByField(array('id' => $params['blog_url_type'], 'status' => blogBlogModel::STATUS_PUBLIC))) {
                        $blogs[] = $blog;
                    }
                } elseif (strlen($blog_url)) {
                    if ($blog = $blog_model->getBySlug($blog_url, true, array('id', 'name', 'url'))) {
                        $blogs[] = $blog;
                    }
                } else {
                    $blogs = blogHelper::getAvailable();
                    if (!isset($params['post_url'])) {

                        if ((count($blogs) > 1) || ($params['blog_url_type'] == 0)) {
                            $main_page = true;
                        }
                    }

                }

                if ($blogs) {
                    if ((count($blogs) == 1) && (($params['blog_url_type'] != 0) || strlen($blog_url))) {
                        $blog = reset($blogs);
                        $params['blog_id'] = intval($blog['id']);
                        $params['blog_url'] = $blog['url'];

                        if (!$main_page) {
                            $routing = wa()->getRouting();
                            if ($params['blog_id'] != $routing->getRouteParam('blog_url_type') || isset($params['post_url'])) {
                                $title = $blog['name'];
                            } elseif (!$title) {
                                $title = $blog['name'];
                            }
                        }
                    } else {
                        $params['blog_id'] = array_map('intval', array_keys($blogs));
                    }
                } else {
                    throw new waException(_w('Blog not found'), 404);
                }
                $this->system->getResponse()->setTitle(htmlentities($title, ENT_QUOTES, 'utf-8'));


                waRequest::setParam($params);
                parent::execute($plugin, $module, $action, $default);
            } catch (Exception $e) {
                waRequest::setParam('exception', $e);
                parent::execute(null, 'frontend', 'error');
            }
        } else {
            parent::execute($plugin, $module, $action, $default);
        }
    }
}