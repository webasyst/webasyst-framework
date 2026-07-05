<?php
/**
 * Files tab in UI 2.0
 */
class siteFilemanagerAction extends waViewAction
{
    protected $sub_dirs_decoded = null;

    public function execute()
    {
        /*$domain_id = waRequest::request('domain_id', null, 'int');
        $domains = siteHelper::getDomains(true);
        if (!$domain_id || empty($domains[$domain_id])) {
            throw new waException('Domain not found', 404);
        }
        */
        $this->setLayout(new siteBackendLayout());

        $path = wa()->getDataPath(null, true);
        $dirs = $this->getDirs($path);

        $files_path = waRequest::param('files_path', null, 'string_trim');
        $domain_id = siteHelper::getDomainId();
        $domains = siteHelper::getDomains(true);
        $domain = $domains[$domain_id];
        $page = waRequest::request('page', 1, waRequest::TYPE_INT);

        $domains_decode = [];
        foreach ($domains as $_d) {
            $domains_decode[$_d['name']] = waIdna::dec($_d['name']);
        }

        $this->view->assign([
            'dirs' => $dirs,
            'sub_dirs_decoded' => $this->sub_dirs_decoded,
            'domain_idn' => waIdna::dec(siteHelper::getDomain()),
            'domain_protocol' => $this->getProtocol(),
            'domain_id' => $domain_id,
            'domain' => $domain,
            'files_path' => $files_path,
            'page' => $page,
            'domains_decode' => $domains_decode,
        ]);
    }

    protected function getDirs($path)
    {
        $result = array();
        $files = scandir($path);
        if ($files) {
            foreach ($files as $f) {
                if ($f !== '.' && $f !== '..' && is_dir($path . '/' . $f)) {

                    // make sure it's utf-8 or at least something we can json_encode
                    $f_encoded = $f;
                    if (!preg_match('!!u', $f)) {
                        $f_encoded = @iconv('windows-1251', 'utf-8//ignore', $f);
                        if (!$f_encoded) {
                            $f_encoded = utf8_encode($f);
                        }
                    }

                    if ($sub_dirs = $this->getDirs($path . '/' . $f)) {
                        foreach ($sub_dirs as $s_dir) {
                            if (is_string($s_dir) && (strpos($s_dir, 'xn--') === 0))
                                $this->sub_dirs_decoded[$s_dir] = waIdna::dec($s_dir);
                        }
                        $result[] = array(
                            'id' => $f_encoded,
                            'childs' => $sub_dirs
                        );
                    } else {
                        $result[] = $f_encoded;
                    }
                }
            }
        }
        return $result;
    }

    protected function getProtocol()
    {
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.siteHelper::getDomain().'.php');
        if (file_exists($domain_config_path)) {
            $orig_domain_config = include($domain_config_path);
        }
        $domains = siteHelper::getDomains();
        $domain_id = waRequest::get('domain_id', null, waRequest::TYPE_INT);
        if (!empty($orig_domain_config['ssl_all'])) {
            $protocol = 'https://';
        } elseif ((!$domain_id || $domains[$domain_id] == wa()->getConfig()->getDomain()) && waRequest::isHttps()) { //first site in list or current site
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        return $protocol;
    }
}
