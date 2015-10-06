<?php

class blogImportPluginSettingsAction extends blogPluginsSettingsViewAction
{
    public function execute()
    {
        waHtmlControl::registerControl('ReplaceMap', array($this, 'controlReplaceMap'));
        $this->plugin_id = 'import';
        parent::execute();
        $this->getResponse()
             ->addJs('plugins/import/js/settings.js?'.wa()->getVersion(), true)
             ->addJs('js/jquery.form.js', true);

        $protocols = array();
        $protocols['wordpress'] = array(
            'name'        => _wp('WordPress'),
            'description' => _wp('Import posts and comments from a WordPress blog'),
        );
        $protocols['lj'] = array(
            'name'        => _wp('LiveJournal'),
            'description' => _wp('Import posts from a LiveJournal blog (comments are not imported)'),
        );
        $protocols['webasystSame'] = array(
            'name'        => _wp('WebAsyst Shop-Script (same server)'),
            'description' => _wp('Import news from WebAsyst Shop-Script (older version of the Shop-Script app)'),
        );
        $protocols['webasystRemote'] = array(
            'name'        => _wp('WebAsyst Shop-Script (remote server)'),
            'description' => _wp('Import news from WebAsyst Shop-Script (older version of the Shop-Script app)'),
        );
        $this->view->assign('protocols', $protocols);
    }

    public static function controlReplaceMap($name, $params = array())
    {
        foreach ($params as $field => $param) {
            if (preg_match('/(wrapper|title|description)/', $field)) {
                unset($params[$field]);
            }
        }
        if (!isset($params['value']) || !is_array($params['value'])) {
            $params['value'] = array();
        }
        waHtmlControl::addNamespace($params, $name);
        $columns = array(
            'search'    => waHtmlControl::INPUT,
            'replace'   => waHtmlControl::INPUT,
            'is_regexp' => waHtmlControl::CHECKBOX,
        );
        if (!empty($params['value']) && isset($params['value']['search'])) {
            foreach ($params['value']['search'] as $id => $value) {
                if (empty($value)) {
                    foreach ($columns as $column => $type) {
                        if (isset($params['value'][$column][$id])) {
                            unset($params['value'][$column][$id]);
                        }
                    }
                }
            }
        } else {
            $params['value'] = array();
        }
        if (empty($params['value']['search']) || !count($params['value']['search'])) {
            foreach ($columns as $column => $type) {
                $params['value'][$column][] = false;
            }
        }

        $strings = array(
            'search'  => _wp('Search for'),
            'replace' => _wp('Replace with'),
            'regexp'  => _wp('Reg Exp<br /> (advanced)'),
            'remove'  => _wp('Remove the rule'),
            'add'     => _wp('Add a rule'),
        );
        $control = <<<HTML

<script>
function blog_import_html_control_replace_add(element, eventObject) {
    var row = $(element).parents('table').find('tbody tr:last');
    if(row){
        row = row.clone().insertAfter(row);
        row.find(':text').val('');
        row.find(':checked').attr('checked',false);
    }
    return false;
}
function blog_import_html_control_replace_remove(element, eventObject) {
var row = $(element).parents('tr');
    if($(element).parents('table').find('tbody tr').length > 1) {
        row.remove();
    } else {
        row.find(':text').val('');
        row.find(':checked').attr('checked',false);
    }
    return false;
}
</script>
<table class="zebra">
    <thead>
        <tr>
            <td>{$strings['search']}</td>
            <td>{$strings['replace']}</td>
            <td>{$strings['regexp']}</td>
            <td>&nbsp;</td>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="4">
                <a href="#" class="inline-link" onClick="return blog_import_html_control_replace_add(this,event)"><i class="icon16 add"></i><b><i>{$strings['add']}</i></b></a>
            </td>
        </tr>
    </tfoot>
    <tbody>
HTML;
        foreach ($params['value']['search'] as $id => $value) {
            $control .= "\n\t\t<tr>";
            foreach ($columns as $field => $type) {
                $column_params = $params;
                $column_params['value'] = isset($params['value'][$field][$id]) ? $params['value'][$field][$id] : null;
                waHtmlControl::addNamespace($column_params, $field);
                $control .= "\n\t\t\t<td class='align-center'>".waHtmlControl::getControl($type, '', $column_params)."</td>";
            }
            $control .= <<<HTML

            <td>
                <a href="#" class="inline-link" onClick="return blog_import_html_control_replace_remove(this,event)"><i class="icon16 delete" title="{$strings['remove']}"></i></a>
            </td>
HTML;
            $control .= "\n\t\t</tr>";
        }
        $control .= '</tbody></table>';
        return $control;
    }
}
