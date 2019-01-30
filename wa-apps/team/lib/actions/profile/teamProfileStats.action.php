<?php
/**
 * Stats tab in profile.
 */
class teamProfileStatsAction extends waViewAction
{
    public function execute()
    {
        // Get parameters from GET/POST
        list($start_date, $end_date, $group_by) = self::getTimeframeParams();

        $contact_id = waRequest::request('id', null, waRequest::TYPE_INT);

        $chart_data = self::getChartData($start_date, $end_date, $group_by, $contact_id);

        $status_stats = self::getStatusStats($start_date, $end_date, $group_by, $contact_id);

        $this->view->assign(array(
            'chart_data' => $chart_data,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'group_by' => $group_by,
            'status_stats' => $status_stats,
            'timeframe' => waRequest::request('timeframe'),
            'selected_app_id' => waRequest::request('app_id', null, 'string'),
            'contact_id' => $contact_id,
            'apps' => self::getApps(),
        ));
    }

    protected static function getChartData($start_date, $end_date, $group_by, $contact_id = null)
    {
        // Fetch stats for the chart
        $log_model = new teamWaLogModel();
        $period_data = $log_model->getPeriodByDate(array(
            'group_by' => $group_by,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'contact_id' => $contact_id !== null ? $contact_id : wa()->getUser()->getId()
        ));

        $all_apps = self::getApps();
        $app_ids = array_keys($all_apps);

        // Prepare app info for JS
        $chart_data = array();
        $app_id_requested = waRequest::request('app_id', null, 'string');
        foreach ($app_ids as $app_id) {
            $chart_data[$app_id] = array(
                'id' => $app_id,
                'name' => htmlspecialchars($all_apps[$app_id]['name']),
                'color' => !empty($all_apps[$app_id]['sash_color']) ? $all_apps[$app_id]['sash_color'] : '#aaa',
                'is_visible' => $app_id_requested === null || $app_id_requested == $app_id,
                'data' => array(),
            );
        }

        // Loop over all dates of the period and gather $chart_data[*]['data']
        for ($ts = strtotime($start_date); $ts <= strtotime($end_date); $ts = strtotime(date('Y-m-d', $ts) . ' +1 day')) {
            if ($group_by == 'months') {
                $new_date = date('Y-m-01', $ts);
                if (ifset($date) == $new_date) {
                    continue;
                }
                $date = $new_date;
            } else {
                $date = date('Y-m-d', $ts);
            }

            $apps = ifset($period_data[$date], array());
            foreach ($chart_data as $app_id => &$data) {
                $data['data'][$date] = array(
                    'date' => $date,
                    'value' => ifset($apps[$app_id], 0),
                );
            }
        }

        foreach ($chart_data as $app_id => &$data) {
            $data['data'] = array_values($data['data']);
        }

        return array_values($chart_data);
    }

    protected static function getStatusStats($start_date, $end_date, $group_by, $contact_id = null)
    {
        $tcem = new teamWaContactEventsModel();
        $contact_id = $contact_id !== null ? $contact_id : wa()->getUser()->getId();
        $stats = $tcem->getStats($contact_id, array(
            'group_by' => $group_by,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ));
        return $stats;
    }

    public function getTimeframeParams()
    {
        $timeframe = waRequest::request('timeframe');
        if ($timeframe === 'all') {
            $start_date = null;
            $end_date = null;
        } elseif ($timeframe == 'custom') {
            $from = waRequest::request('from');
            $start_date = $from ? date('Y-m-d 00:00:00', strtotime($from)) : null;

            $to = waRequest::request('to');
            $end_date = $to ? date('Y-m-d 23:59:59', strtotime($to)) : null;
        } else {
            if (!wa_is_int($timeframe)) {
                $timeframe = 90;
            }
            $start_date = date('Y-m-d', time() - $timeframe*24*3600);
            $end_date = null;
        }

        $group_by = waRequest::request('groupby', 'days');
        if ($group_by !== 'months') {
            $group_by = 'days';
        }

        if (!$end_date) {
            $end_date = date('Y-m-d 23:59:59');
        }
        if (!$start_date) {
            $log_model = new teamWaLogModel();
            $start_date = $log_model->getMinDate();
        }
        return array($start_date, $end_date, $group_by);
    }

    protected static function getApps()
    {
        $all_apps = wa()->getApps(true);
        if (wa()->getUser()->isAdmin()) {
            return $all_apps;
        }
        return wa()->getUser()->getApps();
    }
}
