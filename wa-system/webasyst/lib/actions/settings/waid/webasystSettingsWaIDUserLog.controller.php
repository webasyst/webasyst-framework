<?php

class webasystSettingsWaIDUserLogController extends waJsonController
{
    const LIMIT = 100;

    public function execute()
    {
        $user_logs = [];
        $user_id = (int) waRequest::post('user_id', null);
        $page = max((int) waRequest::post('page', 0), 0);

        if ($user_id > 0) {
            $limit = self::LIMIT;
            $offset = $page * $limit;
            $contact_waid = new waContactWaidModel();
            $user_logs = $contact_waid->query(
                "SELECT * FROM wa_log
                WHERE contact_id = :user_id AND action IN ('login', 'waid_auth')
                ORDER BY id DESC LIMIT $offset,$limit",
                ['user_id' => $user_id]
            )->fetchAll();
        } else {
            $this->response['errors'] = [_ws('User not found.')];
        }

        $this->response['next'] = count($user_logs) === self::LIMIT;
        $this->response['data'] = array_map([$this, 'paramFormat'], $user_logs);
    }

    private function paramFormat($user_log = [])
    {
        $json_string = ifset($user_log, 'params', '');
        $params = json_decode($json_string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $params = [];
        }

        if (isset($params['two_fa_time'])) {
            try {
                $params['two_fa_time'] = waDateTime::format('datetime', $params['two_fa_time']);
            } catch (waException $e) {}
        }

        $user_log['params'] = $params;

        $user_log['waid_auth'] = ifset($user_log, 'action', '') == 'waid_auth';

        $user_log['datetime'] = waDateTime::format('datetime', $user_log['datetime']);

        return $user_log;
    }
}
