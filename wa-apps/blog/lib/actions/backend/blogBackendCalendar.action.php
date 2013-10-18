<?php
/**
 * @author WebAsyst Team
 *
 */
class blogBackendCalendarAction extends waViewAction
{

    public function execute()
    {
        $this->getResponse()->setTitle(_w('Calendar'));
        $this->setLayout(new blogDefaultLayout());

        $blog_model = new blogBlogModel();
        $post_model = new blogPostModel();

        $blogs = $blog_model->getAvailable($this->getUser());

        $timezone = wa()->getUser()->getTimezone();
        // Y-m-d -> 2011-01-01
        $month_date = waRequest::get("month");
        if (!$month_date) {
            $month_date = waDateTime::date("Y-m", null, $timezone);
        } elseif ($month_date <= "1970" || $month_date >= "2033" || !strtotime($month_date)) {
            $this->redirect("?action=calendar");
        }
        $month_date = strtotime($month_date);


        $days_count = date("t", $month_date);
        // Numeric representation of the day of the week
        $first_day = date("w", $month_date);
        $last_day = date("w", strtotime(date("Y-m-{$days_count}", $month_date)));

        // first day is 'Sunday'
        if (waLocale::getFirstDay() == 7) {
            $first_day += 1;
            $last_day += 1;
        }
        $first_day = ($first_day == 0) ? 6 : $first_day - 1;
        $last_day = ($last_day == 0) ? 0 : 7 - $last_day;
        $date_start = strtotime("-".$first_day." days", $month_date);
        $date_end = strtotime("+".($days_count + $last_day)." days", $month_date);


        $search_options = array();
        $search_options['datetime'] = array(date("Y-m-d", $date_start), date("Y-m-d", $date_end));
        $search_options['blog_id'] = array_keys($blogs);
        $search_options['status'] = false;

        if (!$this->getUser()->isAdmin($this->getApp())) {
            $search_options['contact_id'] = $this->getUser()->getId();
        }
        $extend_options = array(
            'status' => true,
            'user'   => false,
            'rights' => true,
        );
        $posts = $post_model->search($search_options, $extend_options, array('blog' => $blogs))->fetchSearchAll(false);

        $current_date_start = $date_start;
        $days = array();
        do {
            $week = (int)date("W", $current_date_start);
            $day = (int)date("w", $current_date_start);

            if (waLocale::getFirstDay() == 7 && $day == 0) {
                $week = (int)date("W", strtotime("+1 week", $current_date_start));
            }

            if (!isset($days[$week])) {
                $days[$week] = array();
            }
            $days[$week][$day] = array(
                "date"  => array(
                    'day'   => date("j", $current_date_start),
                    'month' => date("n", $current_date_start),
                    'date'  => date("Y-m-d", $current_date_start),
                ),
                "posts" => array(),
            );
            $current_date_start = strtotime("+1 days", $current_date_start);
        } while ($date_end > $current_date_start);

        foreach ($posts as $post) {
            #post.datetime cast to user timezone
            $week = (int)waDateTime::date("W", $post['datetime'], $timezone);
            $day = (int)waDateTime::date("w", $post['datetime'], $timezone);

            $days[$week][$day]["posts"][] = $post;
        }

        $now_date = waDateTime::date("Y-m-d", null, $timezone);

        $where = '';
        $search = false;
        if ($this->getUser()->isAdmin($this->getApp())) {
            $search = true;
        } else {
            $writeable = array();
            $full = array();
            foreach ($blogs as $id => $blog) {
                if ($blog['rights'] >= blogRightConfig::RIGHT_FULL) {
                    $full[] = $id;
                } elseif ($blog['rights'] >= blogRightConfig::RIGHT_READ_WRITE) {
                    $writeable[] = $id;
                }
            }
            $contact_where = array();
            if ($full) {
                $contact_where[] = "blog_id IN (".implode(', ', $full).")";
            }
            if ($writeable) {
                $contact_where[] .= "contact_id = {$this->getUser()->getId()} AND blog_id IN (".implode(', ', $writeable).")";
            }
            if ($contact_where) {
                $search = true;
                $where .= ' AND ( ('.implode(') OR (', $contact_where).' ) )';
            }
        }

        if ($search) {
            $posts_overdue_prev = $post_model->select("COUNT(*) AS 'cnt'")
                                  ->where("status = '".blogPostModel::STATUS_DEADLINE."' AND datetime < '".date("Y-m-d", $date_start)."' ".$where)
                                  ->limit(1)
                                  ->fetchField('cnt');

            $posts_overdue_next = $post_model->select("COUNT(*) AS 'cnt'")
                                  ->where("status = '".blogPostModel::STATUS_DEADLINE."' AND datetime > '".date("Y-m-d", $date_end)."' AND datetime < '".$now_date."'".$where)
                                  ->limit(1)
                                  ->fetchField('cnt');

            $prev_overdue = $posts_overdue_prev ? true : false;
            $next_overdue = $posts_overdue_next ? true : false;
        } else {
            $prev_overdue = false;
            $next_overdue = false;
        }

        $months = array(
            1  => _ws('January'),
            2  => _ws('February'),
            3  => _ws('March'),
            4  => _ws('April'),
            5  => _ws('May'),
            6  => _ws('June'),
            7  => _ws('July'),
            8  => _ws('August'),
            9  => _ws('September'),
            10 => _ws('October'),
            11 => _ws('November'),
            12 => _ws('December')
        );
        $current_year = date('Y', $month_date);
        $current_month = date('Y', $month_date);
        $boundaries = $post_model->select("MIN(datetime) as min, MAX(datetime) as max")->fetch();

        if ($boundaries) {
            $years = range(
                min(
                    date('Y', strtotime($boundaries['min'])),
                    $current_year
                ),
                max(
                    date('Y', strtotime($boundaries['max'])),
                    $current_year,
                    date('Y')
                )
            );
        } else {
            $now_year = date('Y');
            $years = range(
                min($current_year, $now_year), max($current_year, $now_year)
            );
        }

        $this->view->assign("prev_overdue", $prev_overdue);
        $this->view->assign("next_overdue", $next_overdue);

        $this->view->assign("allow_add", $search);

        $this->view->assign("days", $days);

        $this->view->assign("week_first_sunday", waLocale::getFirstDay() == 7);
        $this->view->assign("current_month", date("n", $month_date));
        $this->view->assign("current_year", date("Y", $month_date));
        $this->view->assign("prev_month", date("Y-m", strtotime("-1 month", $month_date)));
        $this->view->assign("next_month", date("Y-m", strtotime("+1 month", $month_date)));

        $this->view->assign("years", $years);
        $this->view->assign("months", $months);

        // cast to user timezone
        $this->view->assign("today", waDateTime::date("j", null, $timezone));
        $this->view->assign("today_month", waDateTime::date("n", null, $timezone));

        $this->nocache();
    }

    private function nocache()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: post-check=0,pre-check=0");
        header("Cache-Control: max-age=0");
        header("Cache-Control: no-store");
        header("Pragma: no-cache");
    }
}