<?php
/** A list of localized strings to use in JS. */
class contactsBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();

        // Application locale strings
        foreach(array(
            'Loading...',
            'select country',
            '&lt;no name&gt;',
            '&lt;none&gt;',
            '<no name>',
            'no name',
            'not set',
            'Access',
            'Add another',
            'Actions with selected',
            'Add to category',
            'Add to list',
            'Administrator',
            'All customers',
            'No categories available',
            'Are you sure you want to delete this form?',
            'At least one of these fields must be filled',
            'Cancel',
            'cancel',
            'delete',
            'Checking links to other applications...',
            'Company',
            'Contacts',
            'Show %s records on a page',
            'Contacts will not be deleted.',
            'Customize access',
            'Delete',
            'Delete category',
            'Delete group',
            'Edit category',
            'Edit group',
            'Edit list',
            'Edit filter',
            'Edit',
            'edit search',
            'Email',
            'Export',
            'Facebook',
            'Flickr',
            'Internet',
            'Limited access',
            'Merge',
            'Must be a number.',
            'Incorrect Email address format.',
            'Incorrect URL format.',
            'Name',
            'next',
            'No access',
            'No contacts will be deleted.',
            'or',
            'other',
            'Page not found',
            'Pages',
            'Photo',
            'Picasa',
            'prev',
            'Remove selected',
            'required',
            'Save',
            'Send',
            'Settings',
            'which?',
            'Sort by',
            'This field is required.',
            'Passwords do not match.',
            'Delete this group?',
            'Delete this filter?',
            'Delete filter',
            'Delete this list?',
            'Delete list',
            'Save as a filter',
            'Delete this category?',
            'Exclude from this category',
            'Exclude from this list',
            'Exclude contacts from category &ldquo;%s&rdquo;?',
            'Exclude contacts from list &ldquo;%s&rdquo;?',
            'Exclude',
            'select region',
        ) as $s) {
            $strings[$s] = _w($s);
        }

        // multiple forms
        foreach(array(
            array('contacts selected', 'contact selected'),
        ) as $s) {
            $strings[$s[0]] = array(_w($s[1],$s[0],1), _w($s[1],$s[0],2), _w($s[1],$s[0],5));
        }

        $this->view->assign('strings', $strings ? $strings : new stdClass()); // stdClass is used to show {} instead of [] when there's no strings
        
        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}

// EOF
