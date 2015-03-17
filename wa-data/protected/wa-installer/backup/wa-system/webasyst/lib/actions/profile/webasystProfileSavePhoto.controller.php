<?php

wa('contacts');

/** Contact photo editor, step two: user selected an area to crop. */
class webasystProfileSavePhotoController extends contactsPhotoCropController
{
    protected function getId()
    {
        return wa()->getUser()->getId();
    }
}

