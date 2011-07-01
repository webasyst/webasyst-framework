<?php

/**
 * Search history has a functionality to save favorites which are always shown and never deleted,
 * unless user removes them manually. Currently this is not used, but may be used in future.
 * This controller moves history items to 'history favorites' and back.
 */
class contactsContactsHistoryController extends waJsonController {
    public function execute() {
        $historyModel = new contactsHistoryModel();

        if ( ( $fix = (int)waRequest::get('fix'))) {
            $position = (int)waRequest::get('position');
            $historyModel->fix($fix, $position);
            $this->response['fixed'] = $fix;
        }

        if ( ( $unfix = (int)waRequest::get('unfix'))) {
            $historyModel->fix($unfix, 0);
            $this->response['unfixed'] = $unfix;
        }

        if (waRequest::get('clear')) {
            $type = waRequest::get('ctype');
            $historyModel->prune(0, $type);
            $this->response['cleared'] = 1;
        }
    }
}

// EOF