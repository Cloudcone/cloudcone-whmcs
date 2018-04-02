<?php
/**
 * CloudCone WHMCS Module
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once 'loader.php';

function cloudcone_MetaData() {
    return array(
        'DisplayName' => 'CloudCone',
        'APIVersion' => '1.0',
        'RequiresServer' => true,
        'ServiceSingleSignOnLabel' => 'Manage on CloudCone'
    );
}

function cloudcone_createTables() {
    $pdo = Capsule::connection()->getPdo();
    $pdo->query("CREATE TABLE IF NOT EXISTS `mod_cloudcone` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `hostingid` INT(11) NOT NULL,
        `instanceid` INT(11) NOT NULL,
        PRIMARY KEY (`id`), UNIQUE (`hostingid`)) ENGINE = InnoDB;");

    if ($pdo) {
        return true;
    } else {
        return array(
			'error' => sprintf( $_LANG['ccone']['table_create_error'], 'mod_cloudcone' )
		);
    }
}

function cloudcone_ConfigOptions($params) {
    global $_LANG;

    $create_tables = cloudcone_createTables();
    if(isset($create_tables['error'])) {
		return array(
			sprintf(
				'<font color="red"><b>%s</b></font>',
				$create_tables['error']
			) => array()
		);
	}

    $cc_scripts = cloudcone_get_product_scripts();
    return array(
        $_LANG['ccone']['cpu'] => array(
            'Type' => 'text',
            'Size' => '2',
            'Description' => $_LANG['ccone']['cpu_description'],
            'SimpleMode' => true,
        ),
        $_LANG['ccone']['ram'] => array(
            'Type' => 'text',
            'Size' => '6',
            'Description' => $_LANG['ccone']['ram_description'],
            'SimpleMode' => true,
        ),
        $_LANG['ccone']['disk'] => array(
            'Type' => 'text',
            'Size' => '6',
            'Description' => $_LANG['ccone']['disk_description'],
            'SimpleMode' => true,
        ),
        $_LANG['ccone']['plan'] => array(
            'Type' => 'text',
            'Size' => '6',
            'Description' => $_LANG['ccone']['plan_description'],
            'SimpleMode' => true,
        ),
        $_LANG['ccone']['hypervisor'] => array(
            'Type' => 'text',
            'Size' => '32',
            'Loader' => 'cloudcone_GetHypervisorList',
            'Description' => $_LANG['ccone']['hypervisor_description'],
            'Default' => '0',
            'SimpleMode' => true,
        ),
        'Configurable Options' => array(
            'Type' => 'text',
            'Description' => $_LANG['ccone']['generate_config_notice']."\n".$cc_scripts,
            'SimpleMode' => true,
        )
    );
}

function cloudcone_GetHypervisorList() {
    $hypervisor_list = array('0' => 'CloudCone Public');

    try {
        $pdo = Capsule::connection()->getPdo();
        $q = $pdo->query("SELECT username, password FROM tblservers WHERE type = 'cloudcone' LIMIT 1");

        if ($q && $q->rowCount() > 0) {
            $server_data = $q->fetchObject();
            $password = decrypt($server_data->password);

            $cloudcone = new CloudConeAPI($server_data->username, $password);
            $hypervisors = json_decode($cloudcone->dedicatedHypervisors(), true);

            if ($hypervisors['status'] && !empty($hypervisors['__data']['instances'])) {
                $hypervisor_list['auto'] = 'Private - Auto Select';

                foreach ($hypervisors['__data']['instances'] as $instance) {
                    $hypervisor_list[$instance['node']] = $instance['hostname'];
                }
            }
        }
    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
    }

    return $hypervisor_list;
}

function cloudcone_CreateAccount(array $params) {
    try {
        $product = $params['model']['product'];

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $create_compute = json_decode($cloudcone->computeCreate($params['customfields']['cchost'], (int)$product['configoption1'], (int)$product['configoption2'], (int)$product['configoption3'], 1, (int)$params['configoptions']['Operating System'], 0, 0, 'off', $product['configoption4'], $product['configoption5']), true);

        $serviceid = $params['serviceid'];
        $root_pass = encrypt($create_compute['__data']['root_password']);
        $dedicatedip = $create_compute['__data']['ip'];
        $instanceid = $create_compute['__data']['instance_id'];

        $pdo = Capsule::connection()->getPdo();

        $q = $pdo->prepare("UPDATE tblhosting SET username = 'root', password = ?, dedicatedip = ? WHERE id = ?");
        $q->execute(array($root_pass, $dedicatedip, $serviceid));

        cloudcone_set_instanceid($serviceid, $instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_SuspendAccount(array $params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $cloudcone->computeShutdown($instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_UnsuspendAccount(array $params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $cloudcone->computeBoot($instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_TerminateAccount(array $params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $cloudcone->computeDestroy($instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_Boot($params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $cloudcone->computeBoot($instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_Reboot($params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $cloudcone->computeReboot($instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_Shutdown($params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $cloudcone->computeShutdown($instanceid);

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_Reinstall($params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $compute_reinstall = json_decode($cloudcone->computeReinstall($instanceid, $_POST['os']), true);

        $pdo = Capsule::connection()->getPdo();
        $password = encrypt($compute_reinstall['__data']['root_password']);
        $q = $pdo->prepare("UPDATE tblhosting SET password = ? WHERE id = ?");
        $q->execute(array($password, $params['serviceid']));

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_ResetRootPassword(array $params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);
        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $pdo = Capsule::connection()->getPdo();

        $resetPassword = json_decode($cloudcone->computeResetPassword($instanceid), true);
        if ($resetPassword['status']) {
            $password = $resetPassword['__data']['password'];

            $q = $pdo->prepare("UPDATE tblhosting SET password = ? WHERE id = ?");
            $q->execute(array(encrypt($password), $params['serviceid']));
        }

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_ChangePackage(array $params) {
    try {
        $serviceid = $params['serviceid'];
        $instanceid = cloudcone_get_instanceid($params['serviceid']);
        $product = $params['model']['product'];

        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);
        $compute_resize = json_decode($cloudcone->computeResize($instanceid, (int)$product['configoption1'], (int)$product['configoption2'], (int)$product['configoption3']), true);
    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function cloudcone_ClientAreaCustomButtonArray() {
    return array(
        "Boot" => "Boot",
        "Reboot" => "Reboot",
        "Shutdown" => "Shutdown",
        "CCONE Reset Root Password" => "ResetRootPassword",
        "CCONE Rebuild" => "Reinstall",
    );
}

function cloudcone_AdminCustomButtonArray() {
    return array(
        "Reset Root Password" => "ResetRootPassword",
    );
}

function cloudcone_AdminServicesTabFields(array $params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);

        return array(
            'CloudCone Instance ID' => '<input type="hidden" name="cloudcone_original_instanceid" '
                . 'value="' . htmlspecialchars($instanceid) . '" />'
                . '<input type="text" name="cloudcone_instanceid"'
                . 'value="' . htmlspecialchars($instanceid) . '" />'
        );
    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
    }

    return array();
}

function cloudcone_AdminServicesTabFieldsSave(array $params) {
    $originalFieldValue = isset($_REQUEST['cloudcone_original_instanceid'])
        ? $_REQUEST['cloudcone_original_instanceid']
        : '';

    $newFieldValue = isset($_REQUEST['cloudcone_instanceid'])
        ? $_REQUEST['cloudcone_instanceid']
        : '';

    if ($originalFieldValue != $newFieldValue) {
        try {
            cloudcone_set_instanceid($params['serviceid'], $newFieldValue);

        } catch (Exception $e) {
            logModuleCall(
                'cloudcone',
                __FUNCTION__,
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
    }
}

function cloudcone_ServiceSingleSignOn(array $params) {
    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);
        $redirectTo = "https://app.cloudcone.com/compute/$instanceid/manage";

        return array(
            'success' => true,
            'redirectTo' => $redirectTo,
        );
    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return array(
            'success' => false,
            'errorMsg' => $e->getMessage(),
        );
    }
}

function cloudcone_ClientArea(array $params) {
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';

    try {
        $instanceid = cloudcone_get_instanceid($params['serviceid']);
        $cloudcone = new CloudConeAPI($params['serverusername'], $params['serverpassword']);

        if ($requestedAction == 'graphs') {
            $serviceAction = 'get_graphs';
            $templateFile = 'templates/graphs.tpl';

            $instanceGraphs = json_decode($cloudcone->computeGraphs($instanceid), true);
            if (isset($instanceGraphs['status']) && $instanceGraphs['status']) {
                $instanceGraphs = $instanceGraphs['__data'];
            } else {
                throw new Exception('Unable to retrieve instance statistics');
            }

            return array(
                'tabOverviewReplacementTemplate' => $templateFile,
                'templateVariables' => array(
                    'graphs' => $instanceGraphs
                ),
            );

        } else if ($requestedAction == 'rebuild') {
            $serviceAction = 'rebuild';
            $templateFile = 'templates/rebuild.tpl';

            $config_options = json_decode(cloudcone_get_config_options(), true);
            $os_list = $config_options['Operating System']['sub_options'];

            return array(
                'tabOverviewReplacementTemplate' => $templateFile,
                'templateVariables' => array(
                    'oslist' => $os_list
                ),
            );

        } else if ($requestedAction == 'resetroot') {
            $serviceAction = 'resetroot';
            $templateFile = 'templates/resetroot.tpl';

            return array(
                'tabOverviewReplacementTemplate' => $templateFile
            );

        } else {
            $serviceAction = 'get_stats';
            $templateFile = 'templates/overview.tpl';

            $instanceInfo = json_decode($cloudcone->computeInfo($instanceid), true);
            if (isset($instanceInfo['status']) && $instanceInfo['status']) {
                $instanceInfo = $instanceInfo['__data']['instances'];
                $instanceInfo['password'] = $params['password'];
            } else {
                throw new Exception('Unable to retrieve instance information');
            }

            return array(
                'tabOverviewReplacementTemplate' => $templateFile,
                'templateVariables' => array(
                    'instance' => $instanceInfo
                ),
            );
        }

    } catch (Exception $e) {
        logModuleCall(
            'cloudcone',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return array(
            'tabOverviewModuleOutputTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}
