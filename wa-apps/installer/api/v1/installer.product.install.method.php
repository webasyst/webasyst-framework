<?php

class installerProductInstallMethod extends waAPIMethod
{
    protected $method = 'POST';

    public function execute()
    {
        $data = $this->getData();
        $slug = $data['slug'];

        if (!$this->isValidSlug($slug)) {
            throw new waAPIException('slug_not_supported', 'Only application slugs supported for now', 400);
        }

        $result = $this->install($slug);

        // Already installed - 200 OK
        if (!$result['status'] && $result['details']['error'] === 'already_installed') {
            $this->response = true;
            wa()->getResponse()->setStatus(200);
            return;
        }

        // Error cases
        if (!$result['status']) {
            $status_code = 500;
            $details = $result['details'];

            $error = $details['error'];
            $error_description = $details['error_description'];
            unset($details['error'], $details['error_description']);

            switch ($error) {
                case 'in_progress':
                case 'in_developer_mode':
                case 'requirements_not_satisfied':
                    $status_code = 409;
                    break;
                case 'license_required':
                    $status_code = 402;
                    $error_description = 'License required to install this product';
                    break;
                case 'check_installed_fail':
                case 'product_not_found':
                    $status_code = 404;
                    break;
            }

            throw new waAPIException($error, $error_description, $status_code, $details);
        }

        // Just installed - 201 Created
        $this->response = true;
        wa()->getResponse()->setStatus(201);
    }

    protected function prepareInstall($slug)
    {
        try {
            $updater = new waInstaller(waInstaller::LOG_TRACE);
            $state = $updater->getState();
            if (!isset($state['stage_status'])
                || $state['stage_status'] == waInstaller::STATE_COMPLETE
                || (
                    ($state['stage_name'] != waInstaller::STAGE_NONE)
                    && ($state['heartbeat'] > (waInstaller::TIMEOUT_RESUME + 5))
                )
                || (
                    ($state['stage_name'] == waInstaller::STAGE_UPDATE)
                    && ($state['heartbeat'])
                )
                || (
                    ($state['stage_status'] == waInstaller::STATE_ERROR)
                    && ($state['heartbeat'])
                )
                || (
                    ($state['stage_name'] == waInstaller::STAGE_NONE)
                    && ($state['heartbeat'] === false)
                )
            ) {
                $updater->setState();
                $state = $updater->getState();

                $apps = installerHelper::getInstaller();

                $items = $apps->getUpdates(null, [$slug => []]);

                if (empty($items[$slug])) {
                    $updater->flush();
                    return [
                        'status' => false,
                        'details' => [
                            'error' => 'product_not_found',
                            'error_description' => _w('Product not found'),
                        ]
                    ];
                }

                $info = $items[$slug];

                if (empty($info['download_url'])) {
                    $updater->flush();
                    return [
                        'status' => false,
                        'details' => [
                            'error' => 'cant_resolve_download_url',
                            'error_description' => _w('Download url can not resolve download url')
                        ],
                    ];
                }

                if (empty($info['applicable'])) {

                    $info['product_id'] = $slug;
                    $checker = new installerRequirementsChecker([$info]);
                    $warnings = $checker->check();

                    if ($warnings && $warnings[$slug]) {
                        $error_description = join("\n", $warnings[$slug]);
                    } else {
                        $error_description = _w('Requirements not satisfied');
                    }

                    $updater->flush();

                    return [
                        'status' => false,
                        'details' => [
                            'error' => 'requirements_not_satisfied',
                            'error_description' => $error_description,
                            'warnings' => $warnings,
                        ],
                    ];
                }

                if ($info['action'] !== waInstallerApps::ACTION_INSTALL) {
                    $updater->flush();
                    return [
                        'status' => false,
                        'details' => [
                            'error' => 'already_installed',
                            'error_description' => '',
                        ]
                    ];
                }

                $target = 'wa-apps/'.$slug;

                $path = wa()->getCachePath(sprintf('update.%s.php', $state['thread_id']), 'installer');
                waUtils::varExportToFile([
                    $target => [
                        'source'    => $info['download_url'],
                        'target'    => $target,
                        'slug'      => $slug,
                        'real_slug' => $slug,
                        'md5'       => !empty($info['md5']) ? $info['md5'] : null,
                        'pass'      => false,
                        'name'      => $info['name'],
                        'icon'      => $info['icon'],
                        'update'    => !empty($info['installed']),
                        'subject'   => empty($info['subject']) ? 'system' : $info['subject'],
                        'edition'   => empty($info['edition']) ? true : $info['edition'],
                    ]
                ],
                $path);

            } else {
                $updater->flush();
                return [
                    'status' => false,
                    'details' => [
                        'error' => 'in_progress',
                        'error_description' => _w('Update is already in progress. Please wait while previous update session is finished before starting update session again.'),
                    ]
                ];

            }
        } catch (Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            return [
                'status' => false,
                'details' => [
                    'error' => 'install_preparation_panic',
                    'error_description' => $msg,
                ]
            ];
        }

        return [
            'status' => true,
            'details' => [
                'state' => $state
            ],
        ];
    }

    protected function install($slug)
    {
        try {
            $installer = new waInstallerApps();
            $app_info = $installer->getItemInfo($slug);
            
            if (!$app_info) {
                return [
                    'status' => false,
                    'details' => [
                        'error' => 'product_not_found',
                        'error_description' => _w('Product not found'),
                    ],
                ];
            }

            if ($app_info['installed']) {
                return [
                    'status' => false,
                    'details' => [
                        'error' => 'already_installed',
                        'error_description' => '',
                    ]
                ];
            }

        } catch (Exception $ex) {
            return [
                'status' => false,
                'details' => [
                    'error' => 'check_installed_fail',
                    'error_description' => '',
                ]
            ];
        }

        if (installerHelper::isDeveloper()) {
            $msg = _w('Unable to install the product (developer mode is on).');
            $msg .= "\n"._w("A .git or .svn directory has been detected. To ignore the developer mode, add option 'installer_in_developer_mode' => true to wa-config/config.php file.");
            return [
                'status' => false,
                'details' => [
                    'error' => 'in_developer_mode',
                    'error_description' => $msg,
                ],
            ];
        }

        $result = $this->prepareInstall($slug);
        if (!$result['status']) {
            return $result;
        }

        $state = $result['details']['state'];

        try {

            $thread_id = $state['thread_id'];
            $controller = new installerUpdateExecuteController([
                'thread_id' => $thread_id,
                'send_response' => false,
                'is_trial' => false,
                'install' => true,
                'mode' => 'raw'
            ]);
            $controller->execute();

            if ($controller->errors) {
                $errors = [];
                foreach ($controller->errors as $error) {
                    $errors[] = $error[0];
                }
                $error_description = join("\n", $errors);

                if ($controller->status_code === 402) {
                    return [
                        'status' => false,
                        'details' => [
                            'error' => 'license_required',
                            'error_description' => $error_description,
                        ],
                    ];
                }

                return [
                    'status' => false,
                    'details' => [
                        'error' => 'install_fail',
                        'error_description' => $error_description,
                    ],
                ];
            }

            if (!empty($controller->response['current_state'])) {
                $state = $controller->response['current_state'];
                if (!empty($state['error'])) {
                    return [
                        'status' => false,
                        'details' => [
                            'error' => 'install_fail',
                            'error_description' => $state['error'],
                        ],
                    ];
                }
            }

            // consider that installation done OK

        } catch (Exception $ex) {
            $msg = installerMessage::getInstance()->raiseMessage($ex->getMessage(), installerMessage::R_FAIL);
            return [
                'status' => false,
                'details' => [
                    'error' => 'install_panic',
                    'error_description' => $msg,
                ],
            ];
        }

        return [
            'status' => true,
            'details' => [],
        ];
    }

    protected function isValidSlug($slug)
    {
        $parts = explode('/', $slug);
        return count($parts) === 1;
    }

    protected function getData()
    {
        $data = waUtils::extractValuesByKeys(waRequest::post(), ['slug'], false);
        $data['slug'] = is_scalar($data['slug']) ? strval($data['slug']) : '';
        return $data;
    }
}
