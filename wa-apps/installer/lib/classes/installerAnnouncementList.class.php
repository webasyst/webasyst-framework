<?php

class installerAnnouncementList
{
    private $cache = [];
    protected $filters = [];
    protected $with_grouping = false;

    /**
     * For webasyst Framework UI v1.3, will shown in top of header
     */
    const PLACE_HEADER_TOP = 'header_top';

    /**
     * For webasyst Framework UI v2.0, will shown in notification block (under bell icon)
     */
    const PLACE_NOTIFICATION = 'notification';

    public function withFilteredByApp($app_id)
    {
        if ($app_id) {
            $this->filters['app_id'] = $app_id;
        }
        return $this;
    }

    /**
     * Get grouped by places list of announcements
     * @return array $result: <place> => $announcements, where place is self::PLACE_TOP_* constant
     *      string      $result[<place>][<key>]['html']
     *      bool        $result[<place>][<key>]['always_open']
     *      string|null $result[<place>][<key>]['app_id']
     */
    public function getList()
    {
        return $this->getFromCache(__METHOD__, function () {
            $list = $this->selectList();
            return $this->groupByPlace($list);
        });
    }

    /**
     * Get list of announcements that should be placed in top header location
     * @return array $announcements
     *      string      $announcements[<key>]['html']
     *      bool        $announcements[<key>]['always_open']
     *      string|null $announcements[<key>]['app_id']
     */
    public function getTopHeaderList()
    {
        $list = $this->getList();
        return isset($list[self::PLACE_HEADER_TOP]) ? $list[self::PLACE_HEADER_TOP] : [];
    }

    /**
     * Get list of announcements that should be placed in top notification location
     * @return array $announcements
     *      string      $announcements[<key>]['html']
     *      bool        $announcements[<key>]['always_open']
     *      string|null $announcements[<key>]['app_id']
     */
    public function getNotificationList()
    {
        $list = $this->getList();
        return isset($list[self::PLACE_NOTIFICATION]) ? $list[self::PLACE_NOTIFICATION] : [];
    }

    private function groupByPlace(array $list = [])
    {
        $result = [
            self::PLACE_HEADER_TOP => [],
            self::PLACE_NOTIFICATION => [],
        ];
        foreach ($list as $key => $announcement) {

            // grouping
            foreach ($result as $place => $_) {
                if (isset($announcement['html'][$place])) {
                    $result[$place][$key] = $announcement;

                    // denormalize: html should be string after grouping
                    $result[$place][$key]['html'] = $announcement['html'][$place];
                }
            }
        }

        return $result;
    }

    public function getOne($key)
    {
        $result = $this->selectList([$key]);
        return isset($result[$key]) ? $result[$key] : null;
    }

    protected function selectList(array $keys = [])
    {
        $announcements = [];
        foreach ($this->buildSelect($keys) as $row) {
            $announcements[$row['name']] = $this->unserializeAnnouncement($row['value']);
        }

        if (isset($this->filters['app_id'])) {
            $current_app = $this->filters['app_id'];
            $announcements = array_filter($announcements, function ($announcement) use ($current_app) {
                $app = ifset($announcement['app_id']);
                if (!$app) {
                    return true;
                }

                $apps = waUtils::toStrArray($app);
                return in_array($current_app, $apps, true);
            });
        }

        return $announcements;
    }

    private function buildSelect(array $keys = [])
    {
        $wasm = new waAppSettingsModel();
        $wcsm = new waContactSettingsModel();

        $where = [
            "a.app_id='installer' AND a.name LIKE 'a-%'"
        ];

        $keys = waUtils::toStrArray($keys);
        if ($keys) {
            $where[] = "a.name IN(:keys)";
        }

        $where[] = "c.value IS NULL";

        $where = join(' AND ', $where);

        $sql = "
            SELECT a.name, a.value 
                FROM {$wasm->getTableName()} a
                LEFT JOIN {$wcsm->getTableName()} c ON c.name=a.name AND c.app_id='installer' AND c.contact_id = :contact_id
            WHERE {$where}
            ORDER BY name";

        return $wasm->query($sql, [
            'contact_id' => wa()->getUser()->getId(),
            'keys' => $keys
        ]);
    }


    private function unserializeAnnouncement($row_value)
    {
        // default fields for all protocols versions
        $default_data = [
            'html' => null, // string or array
            'always_open' => false,
            'app_id' => null
        ];

        // default (protocol 1) variant case
        $data = array_merge($default_data, [
            'html' => [
                self::PLACE_HEADER_TOP => $row_value
            ],
        ]);

        try {
            $json = waUtils::jsonDecode($row_value, true);
            // if not array => response from server in protocol version 1
            if (is_array($json)) {
                $data = array_merge($default_data, $json);
                if (isset($data['html'])) {
                    if (is_scalar($data['html'])) {
                        $data['html'] = [
                            self::PLACE_HEADER_TOP => $data['html']
                        ];
                    }
                    if (!is_array($data['html'])) {
                        $data['html'] = [];
                    }
                }
            }
        } catch (Exception $e) {

        }

        return $data;
    }

    private function getFromCache($key, $loader)
    {
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $loader();
        }
        return $this->cache[$key];
    }
}
