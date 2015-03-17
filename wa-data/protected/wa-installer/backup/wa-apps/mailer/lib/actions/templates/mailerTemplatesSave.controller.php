<?php

/**
 * Controller to save template via AJAX.
 * Can be used to save main template data, or params, or template preview image, or everything at once.
 */
class mailerTemplatesSaveController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        $id = waRequest::post('id');
        $data = waRequest::post('data');
        if ($data) {
            if (!empty($data['sender_id'])) {
                $sender_model = new mailerSenderModel();
                $sender = $sender_model->getById($data['sender_id']);
                $data['from_name'] = $sender['name'];
                $data['from_email'] = $sender['email'];
            } else {
                $data['from_name'] = $data['from_email'] = '';
            }
        }

        // Save template
        $template_model = new mailerTemplateModel();
        if ($id) {
            if ($data) {
                $template_model->updateById($id, $data);
            }
        } else {
            // add template
            $data['is_template'] = 1;
            $data['create_datetime'] = date("Y-m-d H:i:s");
            $data['create_contact_id'] = $this->getUser()->getId();
            // save template
            $id = $template_model->insert($data);

            $this->logAction('composed_new_template');
        }

        if (!empty($data['body'])) {
            mailerHelper::copyMessageFiles($id, $data['body']);
        }

        // Save params
        $params = waRequest::post('params');
        if ($params) {
            if (empty($params['sort'])) {
                $params['sort'] = 1;
            }
            $mpm = new mailerMessageParamsModel();
            $mpm->save($id, $params);
        }

        // Delete logo in main storage or replace with new one just uplaoded
        if (waRequest::post('delete_image')) {
            // Delete saved file
            $file = mailerHelper::getTemplatePreviewFile($id);
            if (is_writable($file)) {
                unlink($file);
            }

            // Delete uploaded file
            $file = new mailerUploadedFile('template_preview');
            $file->delete();
        } else {
            // Save logo if uploaded
            $file = new mailerUploadedFile('template_preview');
            if ($file->uploaded()) {
                $full_path = mailerHelper::getTemplatePreviewFile($id, true);
                $filename = basename($full_path);
                $dir = dirname($full_path).'/';
                waFiles::create($dir);

                // Remove old logo file
                if (is_writable($full_path)) {
                    @unlink($full_path);
                }

                try {
                    // Resize and save image
                    $image = $file->waImage();
                    if ($image->width > 213) {
                        $image->resize(213);
                    }
                    if ($image->height > 128) {
                        $image->crop(213, 128, 0, 0);
                    }
                    $image->save($full_path);
                } catch(Exception $e) {}

                $file->delete();

                $this->response['image'] = mailerHelper::getTemplatePreviewUrl($id);
            }
        }

        // Return message id to browser
        $this->response['id'] = $id;
    }
}

