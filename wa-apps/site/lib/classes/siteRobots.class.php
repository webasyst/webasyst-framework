<?php

class siteRobots
{
    protected $domain;
    protected $path;
    protected $robots = array();

    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->path = wa()->getDataPath('data/'.siteHelper::getDomain().'/', true, 'site').'robots.txt';
        if (file_exists($this->path)) {
            $this->robots = file($this->path);
        } else {
            $this->robots = array();
        }
    }

    public function delete($app_id, $route, $save = true)
    {
        if ($this->robots) {
            $delete = false;
            foreach ($this->robots as $i => $row) {
                $row = trim($row);
                if ($delete) {
                    unset($this->robots[$i]);
                }
                if ($row && substr($row, 0, 5) === '# wa ') {
                    $row = explode(' ', $row, 4);
                    if (count($row) >= 4 && $row[2] == $app_id && $row[3] == $route) {
                        unset($this->robots[$i]);
                        $delete = true;
                    } elseif (count($row) == 3 && $row[2] == $app_id) {
                        $delete = false;
                    }
                }
            }
            $this->save();
        }
    }

    public function update($app_id, $old_route, $new_route)
    {
        $this->delete($app_id, $old_route, false);
        $this->add($app_id, $new_route);
    }

    public function add($app_id, $route, $save = true)
    {
        $app_robots = $this->getAppRobots($app_id);
        if (!$app_robots) {
            return;
        }
        // add rules for existed user-agents
        if ($this->robots) {
            foreach ($this->robots as $i => $row) {
                $row = trim($row);
                if ($row && substr(strtolower($row), 0, 11) == 'user-agent:') {
                    $user_agent = trim(substr($row, 11));
                    if (isset($app_robots[$user_agent])) {
                        $this->robots[$i] = $row.(substr($row, -1) == "\n" ? "" : "\n").$this->robotsToString($app_id, $route, $app_robots[$user_agent]);
                        unset($app_robots[$user_agent]);
                    }
                }
            }
        }
        // add new rules
        if ($app_robots) {
            foreach ($app_robots as $user_agent => $robots) {
                $this->robots[] = 'User-agent: '.$user_agent."\n";
                $this->robots[] = $this->robotsToString($app_id, $route, $robots);
                $this->robots[] = "\n";
            }
        }
        $this->save();
    }

    protected function robotsToString($app_id, $route, $robots)
    {
        $url = waRouting::getDomainUrl($this->domain, false).'/'.waRouting::clearUrl($route);
        $result = "# wa ".$app_id." ".$route."\n";
        foreach ($robots as $row) {
            if (strpos($row[1], '[URL]') !== false) {
                $row[1] = str_replace('[URL]', $url, $row[1]);
            }
            $result .= $row[0].": ".$row[1]."\n";
        }
        $result .= "# wa ".$app_id."\n";
        return $result;
    }

    protected function save()
    {
        if ($this->robots || file_exists($this->path)) {
            file_put_contents($this->path, implode("", $this->robots));
        }
    }

    /**
     * @param string $app_id
     * @return array
     */
    protected function getAppRobots($app_id)
    {
        $path = wa()->getAppPath('lib/config/robots.txt', $app_id);
        if (!file_exists($path)) {
            return array();
        }
        $robots = file($path, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        $data = array();
        $user_agent = false;
        foreach ($robots as $str) {
            $str = trim($str);
            if ($str[1] == '#') {
                continue;
            }
            $str = explode(':', $str, 2);
            if (strtolower(trim($str[0])) == 'user-agent') {
                $user_agent = trim($str[1]);
            } else {
                $data[$user_agent][] = array(trim($str[0]), trim($str[1]));
            }
        }
        return $data;
    }
}