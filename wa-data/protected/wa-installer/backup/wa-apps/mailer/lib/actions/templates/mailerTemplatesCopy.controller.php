<?php

/**
 * Create new template (or draft) by copying another template or campaign.
 */
class mailerTemplatesCopyController extends waJsonController
{
    public function execute()
    {
        // Access control
        if (waRequest::request('create_draft')) {
            if (!mailerHelper::isAuthor()) {
                throw new waException('Access denied.', 403);
            }
        } else {
            if (!mailerHelper::isAdmin()) {
                throw new waException('Access denied.', 403);
            }
        }

        $old_id = waRequest::request('id', 0, 'int');
        $this->response = 0;
        if ($old_id) {
            $mm = new mailerMessageModel();
            $tmpl = $mm->getById($old_id);
            if ($tmpl) {

                if (!mailerHelper::campaignAccess($tmpl)) {
                    throw new waException('Access denied.', 403);
                }

                unset($tmpl['id']);
                $tmpl['create_datetime'] = date('Y-m-d H:i:s');
                $tmpl['create_contact_id'] = wa()->getUser()->getId();
                $tmpl['from_name'] = '';
                $tmpl['from_email'] = '';
                $tmpl['reply_to'] = '';
                $tmpl['return_path'] = '';
                unset(
                    $tmpl['status'],
                    $tmpl['send_datetime'],
                    $tmpl['finished_datetime'],
                    $tmpl['sender_id'],
                    $tmpl['priority'],
                    $tmpl['attachments'],
                    $tmpl['list_id']
                );

                if (!waRequest::request('create_draft')) {
                    $tmpl['is_template'] = 1;
                }

                $id = $mm->insert($tmpl);

                // copy images and other files
                $old_data_path = wa()->getDataPath('files/'.$old_id.'/', true, 'mailer');
                $data_path = wa()->getDataPath('files/'.$id.'/', true, 'mailer');
                if (file_exists($old_data_path)) {
                    waFiles::copy($old_data_path, $data_path);
                }

                // replace URLs in message body
                $old_url_prefix = wa()->getDataUrl('files/'.$old_id.'/', true, 'mailer', true);
                $url_prefix = wa()->getDataUrl('files/'.$id.'/', true, 'mailer', true);
                $tmpl['body'] = str_replace($old_url_prefix, $url_prefix, $tmpl['body']);
                $mm->updateById($id, array(
                    'body' => $tmpl['body'],
                ));

                $this->response = $id;
            }
        }
    }
}
