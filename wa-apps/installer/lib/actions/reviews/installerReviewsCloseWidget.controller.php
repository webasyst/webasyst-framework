<?php

class installerReviewsCloseWidgetController extends waJsonController
{
    public function execute()
    {
        installerProductReviewWidget::markAsClosed($this->getUser());
    }
}
