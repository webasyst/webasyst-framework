<?php

class installerReviewsMarkWhenReviewAddedController extends waJsonController
{
    public function execute()
    {
        installerProductReviewWidget::markWhenReviewAdded($this->getUser());
    }
}
