<?php
class installerBackendController extends waViewController
{
    public function execute()
    {
        if (!waRequest::get('_')) {
            $this->setLayout(new installerBackendLayout());
        }
        $this->executeAction(new installerBackendDefaultAction());
    }
}
