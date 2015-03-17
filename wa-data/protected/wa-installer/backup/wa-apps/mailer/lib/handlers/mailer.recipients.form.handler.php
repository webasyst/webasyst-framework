<?php

/**
 * Implements core recipient selection criteria.
 * See recipients.form event description for details.
 */
class mailerMailerRecipientsFormHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $campaign = $params['campaign'];
        $recipients = $params['recipients'];
        $recipients_groups = &$params['recipients_groups'];

        $contacts_plugins = wa('contacts')->getConfig()->getPlugins();
        $exists_pro = isset($contacts_plugins['pro']);

        if (!$exists_pro) {
            $recipients_groups['languages'] = array(
                'name' => _w('Languages'),
                'content' => '',
                'opened' => false,
                'included_in_all_contacts' => true,
                'comment' => _w('Your contacts may be speaking different languages. You can limit the recipient list by selecting only the desired languages.'),

                // not part of event interface, but used internally here
                'selected' => array(),
                'you_need_contacts' => true,
            );

            $recipients_groups['categories'] = array(
                'name' => _w('Categories'),
                'content' => '',
                'opened' => false,
                'included_in_all_contacts' => true,
                'comment' => _w('Categories are groups of contacts which you can freely manage in the Contacts application. In addition to manually created categories, there are also system categories created by other Webasyst applications; e.g., Shop-Script or Blog. Those categories contain contacts added by the corresponding applications: customers of the online store or authors of comments posted in the blog.'),

                // not part of event interface, but used internally here
                'selected' => array(),
                'all_selected_id' => false,
                'you_need_contacts' => true,
            );
        }
        $recipients_groups['subscribers'] = array(
            'name' => _w('Subscribers'),
            'content' => '',
            'opened' => false,
            'included_in_all_contacts' => true,
            'comment' => _w('This option allows selecting contacts who have opted for receiving your email newsletters using a subscription form (see Subscribers section).'),

            // not part of event interface, but used internally here
            'all_selected_id' => false,
            'selected' => array(),
        );
        $recipients_groups['flat_list'] = array(
            'name' => _w('Additional emails'),
            'content' => null,
            'opened' => false,
            'comment' => _w('Use this field  to manually enter any additional email addresses. If such addresses are not yet contained in the Contacts application, they will be added there as new contacts once the sending of this message is completed.'),

            // not part of event interface, but used internally here
            'count' => 0,
            'ids' => array(),
            'all_emails' => '',
            'you_need_contacts' => true,
        );

        if ($exists_pro) {
            $recipients_groups['contacts_lists'] = array(
                'name' => _w('Contact lists'),
                'content' => '',
                'opened' => false,
                'included_in_all_contacts' => true,
                'comment' => '',

                // not part of event interface, but used internally here
                'selected' => array(),
                'all_selected_id' => false,
                'you_need_contacts' => true,
            );

            $recipients_groups['prosearch'] = array(
                'name' => _w('Contact search'),
                'content' => '',
                'opened' => false,
                'included_in_all_contacts' => true,
                'comment' => "comment",

                // not part of event interface, but used internally here
                'selected' => array(),
                'all_selected_id' => false,
                'you_need_contacts' => true,
            );
        }

        // Loop through all message_resipients and gather data avout what is selected
        foreach($recipients as $r_id => $value) {

            // Being paranoid...
            if (!strlen($value)) {
                continue;
            }

            // Skip list types supported by plugins
            if ($value{0} == '@') {
                continue;
            }

            // Is it subscribers list id?
            if (wa_is_int($value)) {
                $recipients_groups['subscribers']['selected'][$r_id] = $value;
                $recipients_groups['subscribers']['opened'] = true;
                continue;
            }

            // Is it a list of emails?
            if ($value{0} != '/') {
                // Parse and count emails in this list
                // to count total number of emails
                $flat_list = array();
                $parser = new mailerMailAddressParser($value);
                foreach($parser->parse() as $a) {
                    $flat_list[mb_strtolower($a['email'])] = true;
                }

                $recipients_groups['flat_list']['ids'][] = $r_id;
                $recipients_groups['flat_list']['count'] += count($flat_list);
                $recipients_groups['flat_list']['all_emails'] .= "\n".trim($value);
                $recipients_groups['flat_list']['opened'] = true;
                unset($flat_list);
                continue;
            }

            //
            // Otherwise, it is a ContactsCollection hash.
            //

            // See if the hash is of one of supported types
            if (FALSE !== strpos($value, '/category/')) {
                $category_id = explode('/', $value);
                $category_id = end($category_id);
                if ($category_id && wa_is_int($category_id)) {
                    $recipients_groups['categories']['selected'][$r_id] = $category_id;
                    $recipients_groups['categories']['opened'] = true;
                } else {
                    $recipients_groups['categories']['all_selected_id'] = $r_id;
                }
            } else if (FALSE !== strpos($value, '/contacts/view/')) {
                $category_list_id = explode('/', $value);
                $category_list_id = end($category_list_id);
                if ($category_list_id && wa_is_int($category_list_id)) {
                    $recipients_groups['contacts_lists']['selected'][$r_id] = $category_list_id;
                    $recipients_groups['contacts_lists']['opened'] = true;
                } else {
                    $recipients_groups['contacts_lists']['all_selected_id'] = $r_id;
                }
            } else if (FALSE !== strpos($value, '/contacts/prosearch/')) {
                $recipients_groups['prosearch']['selected'][$r_id] = $value;
                $recipients_groups['prosearch']['opened'] = true;
            } else if (FALSE !== strpos($value, '/locale=')) {
                $locale = explode('=', $value);
                $locale = end($locale);
                $recipients_groups['languages']['selected'][$r_id] = $locale;
                $recipients_groups['languages']['opened'] = true;
            } else if ($value == '/') {
                $recipients_groups['categories']['all_selected_id'] = $r_id;
            } else {
                // Not one of supported hash types. Ignore it.
                continue;
            }
        }

        //
        // Now, as we collected data about which categoies, locales, etc. are selected,
        // use it to prepare HTML parts for the form.
        //

        if (!$exists_pro) {
            try {
                $recipients_groups['languages']['content'] = trim(wao(new mailerCampaignsRecipientsBlockLanguagesAction($recipients_groups['languages']))->display());
            } catch (Exception $e) {
                // hide languages group when nothing is selected and there's only one language
                unset($recipients_groups['languages']);
            }

            try {
                $recipients_groups['categories']['content'] = trim(wao(new mailerCampaignsRecipientsBlockCategoriesAction($recipients_groups['categories']))->display());
            } catch (Exception $e) {
                // hide categories block when nothing is selected and there are no available categories
                unset($recipients_groups['categories']);
            }
        }
        else {
            $recipients_groups['prosearch']['content'] = contactsHelper::getSearchForm();
            if (!$recipients_groups['prosearch']['content']) {
//            $recipients_groups['prosearch']['content'] = trim(wao(new mailerCampaignsRecipientsBlockContactsProSearchAction($recipients_groups['prosearch']))->display());
//        }
//        else {
                unset($recipients_groups['prosearch']);
            }

            $recipients_groups['contacts_lists']['content'] = trim(wao(new mailerCampaignsRecipientsBlockContactsProContactsListsAction($recipients_groups['contacts_lists']))->display());
        }

        $recipients_groups['subscribers']['content'] = trim(wao(new mailerCampaignsRecipientsBlockSubscribersAction($recipients_groups['subscribers']))->display());

        $recipients_groups['flat_list']['content'] = trim(wao(new mailerCampaignsRecipientsBlockFlatListAction($recipients_groups['flat_list']))->display());
        if ($recipients_groups['flat_list']['count']) {
            $recipients_groups['flat_list']['name'] .= '<span class="hide-when-modified"> ('.$recipients_groups['flat_list']['count'].')</span>';
        }
    }
}

