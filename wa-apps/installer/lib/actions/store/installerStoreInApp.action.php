<?php
/**
 * Used to show installer store inside other apps.
 */
class installerStoreInAppAction extends installerItemsAction
{
    public function execute()
    {
        parent::execute();

        $this->store_path = $this->buildStorePath([
            'filters'    => $this->getFilters(),
            'in_app'     => true,
        ]);
    }

    protected function buildStorePath($params)
    {
        return '?'.http_build_query($params);
    }
}
