<?php

/**
 * List of campaigns sent and currently sending.
 */
class mailerCampaignsArchiveAction extends waViewAction
{
    public static function getArchiveStates()
    {
        return array(
            mailerMessageModel::STATUS_CONTACTS,
            mailerMessageModel::STATUS_SENDING,
            mailerMessageModel::STATUS_SENDING_PAUSED,
            mailerMessageModel::STATUS_SENDING_ERROR,
            mailerMessageModel::STATUS_SENT,
        );
    }

    public function execute()
    {
        // POST parameters
        $search = waRequest::request('search');
        $start  = waRequest::request('start', 0, 'int');
        $limit  = 50;
        $order  = waRequest::request('order', '!id');

        $mm = new mailerMessageModel();

        // Prepare search words
        $escaped_search = array();
        $search_parts = array();
        foreach(preg_split('~\s+~su', $search) as $part) {
            $part = trim($part);
            if (strlen($part) > 0) {
                $search_parts[] = $part;
                $escaped_search[] = $mm->escape($part, 'like');
            }
        }

        // Get campaigns
        list($messages, $total_rows) = $mm->getListView($escaped_search, $start, $limit, $search_parts ? '!id' : $order);

        // Helper to highlight search words
        $replace = array();
        foreach($search_parts as $part) {
            $replace['~('.preg_quote($part, '~').')~iu'] = '<span class="highlighted">\1</span>';
        }

        // Add empty stats and highlight search words
        $sending_count = 0;
        foreach($messages as $id => &$m) {

            $m['opened_count_available'] = self::isOpenedCountAvailable($m);
            $m['has_unsubscribe_link'] = self::hasUnsubscribeLink($m);

            if ($search_parts) {
                // Strip tags from text
                $text = preg_replace('~<(title|style[^>]*)>[^<]*</(title|style)>~', ' ', $m['body']);
                $text = str_replace('<', ' <', $text);
                $text = trim(strip_tags($text));
                unset($m['body']);

                // Highlight found words in text and subject, and ensure that every word is present.
                // Have to check it in PHP because MySQL search may erroneously find text in HTML markup (e.g. 'center').
                $m['subject'] = htmlspecialchars($m['subject']);
                foreach($replace as $regex => $replacement) {
                    $count_body = $count_subject = 0;
                    $text = preg_replace($regex, $replacement, $text, -1, $count_body);
                    $m['subject'] = preg_replace($regex, $replacement, $m['subject'], -1, $count_subject);
                    if ($count_subject + $count_body <= 0) {
                        unset($messages[$id]);
                        continue 2;
                    }
                }

                // Only show part of the text, preferably containing highlights.
                $pos = mb_strpos($text, '<span class="highlighted">');
                $pos = max(0, $pos-50);
                $max_pos = mb_strlen($text);
                $text = mb_substr($text, $pos, 350);
                if ($pos > 50) {
                    $text = '...'.$text;
                }
                if ($pos + 350 < $max_pos) {
                    $text .= '...';
                }
                $m['text'] = $text;
            } else {
                // Add empty stats
                foreach(array('recipients_num', 'bounced_num', 'opened_num', 'clicked_num', 'unsubscribed_num', 'percent_complete') as $k) {
                    $m[$k] = '';
                }
            }

            $m['finished_datetime_formatted'] = self::formatListDate($m['finished_datetime']);
            if ($m['status'] != mailerMessageModel::STATUS_SENT) {
                $sending_count++;
            }
        }
        unset($m);

        // Add stats to messages
        if ($messages && !$search_parts) {
            $mlm = new mailerMessageLogModel();
            $stats = $mlm->getStatsByMessage(array_keys($messages));
            foreach($stats as $message_id => $s) {
                $messages[$message_id]['recipients_num'] = 0;
                for($i = -4; $i < 6; $i++) {
                    $s[$i] = ifset($s[$i], 0);
                    $messages[$message_id]['recipients_num'] += $s[$i];
                }

                $messages[$message_id]['processed_num']     = $messages[$message_id]['recipients_num'] - $s[0];
                $messages[$message_id]['exceptions_num']    = $s[-3] + $s[-4];
                $messages[$message_id]['bounced_num']       = $s[-1] + $s[-2];
                $messages[$message_id]['not_sent_num']      = $s[0];
                $messages[$message_id]['sent_num']          = $messages[$message_id]['processed_num'] - $messages[$message_id]['bounced_num'];
                $messages[$message_id]['opened_num']        = $s[4] + $s[3] + $s[2];
                $messages[$message_id]['unsubscribed_num']  = $s[5];

                if ($messages[$message_id]['recipients_num'] - $messages[$message_id]['exceptions_num'] > 0) {
                    $messages[$message_id]['percent_complete'] = ($messages[$message_id]['recipients_num'] - $messages[$message_id]['exceptions_num'] - $messages[$message_id]['not_sent_num'])*100.0 / ($messages[$message_id]['recipients_num'] - $messages[$message_id]['exceptions_num']);
                } else {
                    $messages[$message_id]['percent_complete'] = 100;
                }
                $messages[$message_id]['percent_complete'] = round($messages[$message_id]['percent_complete']);
            }

            // Add return path errors
            $rpm = new mailerReturnPathModel();
            $return_path_errors = $rpm->getErrors();
            foreach($messages as &$m) {
                $m['error'] = '';
                if (!empty($return_path_errors[$m['return_path']]) && strtotime(ifempty($m['finished_datetime'], $m['send_datetime'])) > time() - mailerConfig::RETURN_PATH_CHECK_PERIOD) {
                    $m['error'] = _w('Error checking Return-path mailbox');
                }
            }
            unset($m);
        }

        mailerHelper::assignPagination($this->view, $start, $limit, $total_rows);
        $this->view->assign('order', $order);
        $this->view->assign('search', $search_parts ? $search : '');
        $this->view->assign('sending_count', $sending_count);
        $this->view->assign('search_url_append', $search ? $search.'/' : '');
        $this->view->assign('messages', $messages);
    }

    public static function formatListDate($dt)
    {
        if (!$dt) {
            return '';
        }
        if(!wa_is_int($dt)) {
            $ts = strtotime($dt);
        } else {
            $ts = $dt;
            $dt = date('Y-m-d H:i:s', $ts);
        }

        if (date('Y-m-d', $ts) == date('Y-m-d')) {
            return _ws('Today').' '.waDateTime::format('time', $dt, wa()->getUser()->getTimezone());
        } else if (date('Y-m-d', $ts) == date('Y-m-d', time() - 3600*24)) {
            return _ws('Yesterday').' '.waDateTime::format('time', $dt, wa()->getUser()->getTimezone());
        } else {
            return waDateTime::format('humandate', $dt, wa()->getUser()->getTimezone());
        }
    }

    public static function isOpenedCountAvailable($campaign)
    {
        $url = '/wa-data/public/mailer/files/';
        return preg_match('~<img[^<]*src="[^"]*'.$url.'[^"]+\.(jpg|jpeg|png|gif)~is', $campaign['body']);
    }

    public static function hasUnsubscribeLink($campaign)
    {
        return false !== strpos($campaign['body'], '{$unsubscribe_link}');
    }
}

