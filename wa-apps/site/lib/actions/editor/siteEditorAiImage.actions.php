<?php

class siteEditorAiImageActions extends waJsonActions
{
    protected const AI_FACILITY_CREATE = 'image_create';
    protected const IMAGE_PREFIX = 'img-';

    public function generateAction()
    {
        $prompt = waRequest::post('prompt', '', waRequest::TYPE_STRING_TRIM);
        $aspect_ratio = waRequest::post('aspect_ratio', 'auto', waRequest::TYPE_STRING_TRIM);

        $image_data_urls = $this->getImageReferences();

        try {
            $request = (new siteAiImageRequest())
                ->loadFieldsFromApi(self::AI_FACILITY_CREATE)
                ->setFieldValues([
                    'aspect_ratio' => $aspect_ratio,
                ]);

            if ($image_data_urls) {
                $request->setFieldValue('images', $image_data_urls);
            }

            $request->setPrompt($prompt);
            wa()->getStorage()->close();

            if (function_exists('set_time_limit')) {
                @set_time_limit(299);
            }

            $response = $request->generate([
                'timeout' => 280,
            ]);

            if (!empty($response['error_description'])) {
                if (ifset($response, 'error', null) === 'payment_required') {
                    $balance = (new waServicesApi)->getBalanceCreditUrl('AI');
                    if (ifset($balance, 'response', 'url', false)) {
                        $error_description = '<div class="text-orange">'.$response['error_description'].'</div>';
                        $error_description = str_replace('</a>', ' <i class="fas fa-external-link-alt fa-sm"></i></a>', $error_description);
                        $link_attr = 'href="'.$balance['response']['url'].'"';
                        $response['error_description'] = str_replace('%s', $link_attr, $error_description);
                    }
                }

                throw new waException($response['error_description']);
            } elseif (!empty($response['error'])) {
                throw new waException($response['error']);
            }

            $result_images = ifset($response, 'images', []);
            if (!$result_images) {
                throw new waException(_w('No image in the Webasyst ID API response.'), 500);
            }

            $formatName = function($data_url) {
                $file_ext = $this->resolveExtensionFromData($data_url);
                return self::IMAGE_PREFIX.md5($data_url).$file_ext;
            };

            $this->response = array_map(function($img) use ($formatName) {
                $data_url = ifset($img, 'data_url', '');
                return [
                    'name'     => $formatName($data_url),
                    'data_url' => $data_url,
                ];
            }, $result_images);
        } catch (Exception $e) {
            $this->errors = [
                'id' => 'ai_generate_image_error',
                'text' => $e->getMessage(),
            ];
        }
    }

    public function getServiceWorkerAction()
    {
        header('Content-Type: application/javascript; charset=UTF-8');
        exit(wa('site')->getView()->fetch('js/editor/AiImageServiceWorker.js'));
    }


    protected function resolveExtensionFromData($image_data)
    {
        $map = [
            'image/jpeg' => '.jpg',
            'image/jpg'  => '.jpg',
            'image/gif'  => '.gif',
            'image/webp' => '.webp',
            'image/png'  => '.png',
        ];

        foreach ($map as $m => $format) {
            if (substr($image_data, 5, strlen($m)) === $m) {
                return $format;
            }
        }

        return '.png';
    }

    protected function getImageReferences()
    {
        $images = [];
        $reference_files = waRequest::file('reference_files');
        foreach ($reference_files as $file) {
            $image = $this->getUploadedImageData($file);
            if ($image) {
                $images[] = $image;
            }
        }

        return $images;
    }

    protected function getUploadedImageData(waRequestFile $file)
    {
        if ($file->error || !file_exists($file->tmp_name)) {
            return null;
        }

        $tmp_image_path = $file->tmp_name;
        $custom_temp_created = false;

        // Resize image if too large
        if ($file->size > 1000000) {
            try {
                $image = waImage::factory($file);
                if ($image->width + $image->height > 3500 && ($image->width > 2000 || $image->height > 2000)) {
                    $image->resize(2000, 2000);

                    $temp_dir = wa()->getTempPath('upload', 'site');
                    $tmp_image_path = $temp_dir.'/'.uniqid(self::IMAGE_PREFIX, true).'.'.$image->getExt();

                    $image->save($tmp_image_path, 92);
                    $custom_temp_created = true;
                }
                unset($image);
            } catch (Throwable $e) {
                unset($image);
            }
        }

        $file_content = file_get_contents($tmp_image_path);
        if ($file_content === false) {
            return null;
        }

        $mime_type = waFiles::getMimeType('.'.$file->extension);

        $image_data_url = 'data:'.$mime_type.';base64,'.base64_encode($file_content);

        if ($custom_temp_created && file_exists($tmp_image_path)) {
            try {
                waFiles::delete($tmp_image_path);
            } catch (Throwable $e) {
            }
        }

        return $image_data_url;
    }
}
