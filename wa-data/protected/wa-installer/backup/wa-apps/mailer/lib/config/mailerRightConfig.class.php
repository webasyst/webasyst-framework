<?php

class mailerRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('author', _w('Author: can create their own campaigns and send them'), 'checkbox');
        $this->addItem('inspector', _w('Inspector: can only view all existing campaigns, but can not edit or send them'), 'checkbox');
    }

    public function setDefaultRights($contact_id)
    {
        return array(
            'author' => 1,
        );
    }

    public function getHTML($rights = array(), $inherited=null)
    {
        $html = parent::getHTML($rights, $inherited);
        $pre_text = _w('NOTE: Limited access does not allow to modify transport settings and manage subscribers.');

        // when there's no right to see all requests then always disable creation of new requests
        return <<<EOF
        {$html}
        <div class="block">{$pre_text}</div>
        <script>
            // Never allow to save empty set of rights
            $('#c-ar-dialog form').bind('wa.change', function() {
                if ($('#c-ar-dialog form td i.yes:first').length <= 0) {
                    $('#c-ar-dialog .dialog-buttons :submit').attr('disabled', true);
                } else {
                    $('#c-ar-dialog .dialog-buttons :submit').attr('disabled', false);
                }
            }).change();
        </script>
EOF;
    }
}

