<?php

require_once 'modules/admin/models/ServerPlugin.php';
require_once 'plugins/server/virtualizor/sdk/admin.php';

class PluginVirtualizor extends ServerPlugin
{
    public $features = [
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true
    ];

    private $host;
    private $key;
    private $pass;
    private $api;

    public function getVariables()
    {
        $variables = [
            lang('Name') => [
                'type' => 'hidden',
                'description' => 'Used by CE to show plugin - must match how you call the action function names',
                'value' => 'Virtualizor'
            ],
            lang('Description') => [
                'type' => 'hidden',
                'description' => lang('Description viewable by admin in server settings'),
                'value' => lang('Virtualizor control panel integration')
            ],
            lang('API Key') => [
                'type' => 'text',
                'description' => lang('API Key'),
                'value' => '',
                'encryptable'=>true
            ],
            lang('API Pass') => [
                'type' => 'text',
                'description' => lang('API Pass'),
                'value' => '',
                'encryptable'=> true
            ],
            lang('VM Password Custom Field') => [
                'type' => 'text',
                'description' => lang('Enter the name of the package custom field that will hold the VM Password.'),
                'value' => ''
            ],
            lang('VM Hostname Custom Field') => [
                'type' => 'text',
                'description' => lang('Enter the name of the package custom field that will hold the VM hostname.'),
                'value' => ''
            ],
            lang('VM Operating System Custom Field') => [
                'type' => 'text',
                'description' => lang('Enter the name of the package custom field that will hold the VM Operating System.'),
                'value' => ''
            ],
            lang('VM Location Custom Field') => [
                'type' => 'text',
                'description' => lang('Enter the name of the package custom field that will hold the Location (Slave Server ID).  This is an optional setting only used if you have multiple locations/slaves'),
                'value' => ''
            ],
            lang('Actions') => [
                'type' => 'hidden',
                'description' => lang('Current actions that are active for this plugin per server'),
                'value' => 'Create,Delete,Suspend,UnSuspend'
            ],
            lang('Registered Actions For Customer') => [
                'type' => 'hidden',
                'description' => lang('Current actions that are active for this plugin per server for customers'),
                'value' => 'authenticateClient'
            ],
            lang('reseller') => [
                'type' => 'hidden',
                'description' => lang('Whether this server plugin can set reseller accounts'),
                'value' => '0',
            ],
            lang('package_addons') => [
                'type' => 'hidden',
                'description' => lang('Supported signup addons variables'),
                'value' => '',
            ],
            lang('package_vars') => [
                'type' => 'hidden',
                'description' => lang('Whether package settings are set'),
                'value' => '1',
            ],
            lang('package_vars_values') => [
                'type' => 'hidden',
                'description' => lang('Virtualizor Settings'),
                'value' => [
                    'vm_type' => [
                        'type' => 'dropdown',
                        'multiple' => false,
                        'getValues' => 'getVMTypes',
                        'label' => 'VM Type',
                        'description' => lang('Select the type of VM for this package.'),
                        'value' => 'openvz',
                    ],
                    'ip_pool_id' => [
                        'type' => 'text',
                        'label' => 'IP Pool ID',
                        'description' => lang('Enter the ID of the IP pool for this VPS.'),
                        'value' => '',
                    ],
                ]
            ]
        ];
        return $variables;
    }

    public function getVMTypes()
    {
        return [
            'OpenVZ',
            'KVM',
            'Xen',
            'XenServer',
            'XenServer HVM',
            'Xen',
            'Xen HVM',
            'LXC',
            'Virtuozzo OpenVZ',
            'Virtuozzo KVM',
            'Proxmox OpenVZ',
            'Proxmox KVM / QEMU',
            'Proxmox LXC'
        ];
    }

    private function getVirtType($virt)
    {
        switch ($virt) {
            case 'OpenVZ':
                return 'openvz';
            case 'KVM':
                return 'kvm';
            case 'Xen':
                return 'xen';
            case 'XenServer':
                return 'xcp';
            case 'XenServer HVM':
                return 'xcphvm';
            case 'Xen HVM':
                return 'xenhvm';
            case 'LXC':
                return 'lxc';
            case 'Virtuozzo OpenVZ':
                return 'vzo';
            case 'Virtuozzo KVM':
                return 'vzk';
            case 'Proxmox OpenVZ':
                return 'proxo';
            case 'Proxmox KVM / QEMU':
                return 'proxk';
            case 'Proxmox LXC':
                return 'proxl';
        }
    }

    private function setup($args)
    {
        $this->host = $args['server']['variables']['ServerHostName'];
        $this->key = $args['server']['variables']['plugin_virtualizor_API_Key'];
        $this->pass = $args['server']['variables']['plugin_virtualizor_API_Pass'];
        $this->api = new Virtualizor_Admin_API($this->host, $this->key, $this->pass);
    }

    function validateCredentials($args)
    {
    }

    function doDelete($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->delete($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been deleted.';
    }

    function doCreate($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->create($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been created.';
    }

    function doSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->suspend($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been suspended.';
    }

    function doUnSuspend($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($userPackage);
        $this->unsuspend($args);
        $VM_Hostname = $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        return $VM_Hostname . ' has been unsuspended.';
    }

    function unsuspend($args)
    {
        $this->setup($args);
        $id = $args['package']['ServerAcctProperties'];
        $result = $this->api->unsuspend($id);

        if ($result['done'] != true) {
            throw new CE_Exception(implode('<br>', $result['error']));
        }
    }

    function suspend($args)
    {
        $this->setup($args);
        $id = $args['package']['ServerAcctProperties'];
        $result = $this->api->suspend($id);

        if ($result['done'] != true) {
            throw new CE_Exception(implode('<br>', $result['error']));
        }
    }

    function delete($args)
    {
        $this->setup($args);
        $id = $args['package']['ServerAcctProperties'];
        $result = $this->api->delete_vs($id);

        if ($result['done'] != true) {
            throw new CE_Exception(implode('<br>', $result['error']));
        }

        // remove the stored virtual id
        $userPackage = new UserPackage($args['package']['id']);
        $userPackage->setCustomField('Server Acct Properties', '');
    }

    function getAvailableActions($userPackage)
    {
        $args = $this->buildParams($userPackage);
        $this->setup($args);

        if ($args['package']['ServerAcctProperties'] == '') {
            return ['Create'];
        }

        $response = $this->api->listvs(1, 1, ['vpsid' => $args['package']['ServerAcctProperties']]);

        $actions[] = 'Delete';
        if ($response[array_key_first($response)]['suspended'] === '1') {
            $actions[] = 'UnSuspend';
        } else {
            $actions[] = 'Suspend';
        }

        return $actions;
    }

    function create($args)
    {
        $this->setup($args);
        $userPackage = new UserPackage($args['package']['id']);

        // Get Plan Id
        $planId = false;
        $plans = $this->api->plans(1, 1, ['planname' => $args['package']['name_on_server']]);
        if (is_array($plans['plans'])) {
            foreach ($plans['plans'] as $plan) {
                if ($plan['plan_name'] == $args['package']['name_on_server']) {
                    $planId = $plan['plid'];
                }
            }
        }
        if ($planId === false) {
            throw new CE_Exception('Could not find Plan Id for Plan Name: ' . $args['package']['name_on_server']);
        }

        $data = [
            'user_email' => $args['customer']['email'],
            'user_pass' => html_entity_decode($userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE)),
            'hostname' => $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Hostname_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE),
            'rootpass' => html_entity_decode($userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Password_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE)),
            'plid' => $planId,
            'virt' => $this->getVirtType($args['package']['variables']['vm_type']),
            'osid' => $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Operating_System_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE)
        ];

        // Check for slave server id / location
        if ($args['server']['variables']['plugin_virtualizor_VM_Location_Custom_Field'] != '') {
            $data['slave_server'] = $userPackage->getCustomField($args['server']['variables']['plugin_virtualizor_VM_Location_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        }

        $response = $this->api->addvs_v2($data);
        if ($response['done'] != true) {
            throw new CE_Exception(implode("\n", $response['error']));
        }

        $userPackage->setCustomField('Server Acct Properties', $response['vs_info']['vpsid']);
        $userPackage->setCustomField('IP Address', $response['vs_info']['ips'][0]);
        $userPackage->setCustomField('Shared', 0);
    }

    function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        require_once 'plugins/server/virtualizor/sdk/enduser.php';
        $args = $this->buildParams($userPackage);
        $this->setup($args);

        $linkText = $this->user->lang('Login to Virtualizor');

        if ($fromAdmin) {
            $cmd = 'panellogin';
            return [
                'cmd' => $cmd,
                'label' => $linkText
            ];
        } elseif ($getRealLink) {
            $admin = new Virtualizor_Enduser_API($this->host, $this->key, $this->pass, 4083, 1);
            $url = $admin->sso($args['package']['ServerAcctProperties']);

            return array(
                'link'    => '<li><a target="_blank" href="' . $url .'">' .$linkText . '</a></li>',
                'rawlink' =>  $url,
                'form'    => ''
            );
        } else {
            $link = 'index.php?fuse=clients&controller=products&action=openpackagedirectlink&packageId='.$userPackage->getId().'&sessionHash='.CE_Lib::getSessionHash();

            return array(
                'link' => '<li><a target="_blank" href="' . $link .  '">' .$linkText . '</a></li>',
                'form' => ''
            );
        }
    }

    public function dopanellogin($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage);
        return $response['rawlink'];
    }


    public function testConnection($args)
    {
        $this->setup($args);
        $result = $this->api->adminindex();
        if ($result == '') {
            throw new CE_Exception("Connection to server failed.");
        }
    }
}
