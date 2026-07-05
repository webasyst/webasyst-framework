<?php

class developerPortKnockCli extends waCliController
{
    public function execute()
    {

        $ports = array_filter(array_map('intval', preg_split('@[,;\.]@', waRequest::param('p'))));
        $host = waRequest::param('h', waRequest::param(0));

        if (empty($ports) || empty($host) || null !== waRequest::param('help')) {
            $this->showHelp();
        } else {
            $timeout = max(1, waRequest::param('t', 2));
            $options = array(
                'timeout' => $timeout,
            );
            $n = new waNet($options);

            foreach ($ports as $port) {
                $url = sprintf('http://%s:%d', $host, $port);
                try {
                    print "{$url}\n";
                    $n->query($url);
                } catch (waException $ex) {
                }
            }
        }
    }

    private function showHelp()
    {


        echo <<<HELP
Usage: php cli.php developer portKnock example.com -p 80,81,83,82 [-t 5]
\tp comma separated port numbers
\tt optional timeout, default is 2 
HELP;
    }
}
