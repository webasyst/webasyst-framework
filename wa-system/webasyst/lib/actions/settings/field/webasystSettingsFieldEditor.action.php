<?php

class webasystSettingsFieldEditorAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $f = waRequest::param('f');
        $fid = waRequest::param('fid', waRequest::post('fid'));
        $prefix = waRequest::param('prefix', waRequest::post('prefix', 'options'));
        $full_parent = waRequest::param('parent', waRequest::post('parent', null));

        $parent = explode('.', $full_parent);
        $parent = $parent[0];

        $new_field = false;
        if ($f && $f instanceof waContactField) {
            $ftype = $f->getType();
            if ($ftype == 'Select') {
                if ($f instanceof waContactBranchField) {
                    $ftype = 'branch';
                } elseif ($f instanceof waContactRadioSelectField) {
                    $ftype = 'radio';
                }
            }
        } else {
            $ftype = strtolower(waRequest::param('ftype', waRequest::post('ftype', 'string')));
            $f = self::getField($fid, $ftype);
            $new_field = true;
        }
        $ftype = strtolower($ftype);

        $this->view->assign(array(
            'f'         => $f,
            'fid'       => $fid,
            'ftype'     => $ftype,
            'prefix'    => $prefix,
            'parent'    => $parent,
            'uniqid'    => 'fe_'.uniqid(),
            'new_field' => $new_field,
        ));
    }

    protected static function getField($fid, $ftype)
    {
        $f = webasystSettingsFieldSaveController::createFromOpts(array(
            '_type' => $ftype
        ));
        if (!$f) {
            throw new waException('Unknown field type: '.$ftype.' ('.$fid.')');
        }
        return $f;
    }
}