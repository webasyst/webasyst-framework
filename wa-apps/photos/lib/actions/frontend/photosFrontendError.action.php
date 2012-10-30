<?php

class photosFrontendErrorAction extends waViewAction
{
    public function execute()
    {
        $e = $this->params;

        if ($e && ($e instanceof Exception) ) {
            /**
             * @var Exception $e
             */
            $code = $e->getCode();
            if (!$code) {
                $code = 500;
            }

            $message = $e->getMessage();
        } else {
            $code = 404;
            $message = _ws("Page not found");
        }
        $this->getResponse()->setStatus($code);
        $this->getResponse()->setTitle($code.'. '.$message);

        $this->view->assign('error_code', $code);
        $this->view->assign('error_message', $message);
        if ($code == 404) {
            $this->setLayout(new photosDefaultFrontendLayout());
        }
        $this->setThemeTemplate('error.html', waRequest::param('theme', 'default'));
    }
}