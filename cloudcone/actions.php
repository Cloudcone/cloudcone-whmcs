<?php
require_once '../../../init.php';
require_once 'loader.php';

if (!isset($_SESSION['adminid'])) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

/**
 * Handle the action request
 */
if (isset($_POST['cloudcone_action'])) {
    switch ($_POST['cloudcone_action']){
        case 'cloudcone_configure':
            if (isset($_POST['productID'])) {
                cloudcone_setup_configurables($_POST['productID']);
            } else {
                cloudcone_respond(0, 'Invalid product ID: '.$_POST['productID']);
            }
            break;
    }
}

/**
 * Create the configurable options group for CloudCone
 * and enable it for the product
 */
function cloudcone_setup_configurables($product_id) {
    try {
        if (is_numeric($product_id)) {
            $pdo = Capsule::connection()->getPdo();

            $q = $pdo->query("SELECT id FROM tblproductconfiggroups WHERE name = 'CloudCone'");
            if ($q && $q->rowCount() > 0) {
                $config_group_id = $q->fetchObject()->id;
            } else {
                $q = $pdo->query("INSERT INTO tblproductconfiggroups (name, description) VALUES ('CloudCone', 'Auto generated - CloudCone configurable options')");
                if ($q) {
                    $config_group_id = $pdo->lastInsertId();
                }
            }

            if ($config_group_id) {
                $config_options = json_decode(cloudcone_get_config_options(), true);

                foreach ($config_options as $config_option) {
                    $q = $pdo->query("SELECT id FROM tblproductconfigoptions WHERE gid = $config_group_id AND optionname = '".$config_option['optionname']."'");
                    if ($q && $q->rowCount() < 1) {
                        $q = $pdo->prepare("INSERT INTO tblproductconfigoptions (gid, optionname, optiontype, qtyminimum, qtymaximum, hidden) VALUES ($config_group_id, :optionname, :optiontype, :qtyminimum, :qtymaximum, :hidden)");
                        $q->execute(array(
                            ':optionname' => $config_option['optionname'],
                            ':optiontype' => $config_option['optiontype'],
                            ':qtyminimum' => (isset($config_option['qtyminimum'])) ? $config_option['qtyminimum'] : 0,
                            ':qtymaximum' => (isset($config_option['qtymaximum'])) ? $config_option['qtymaximum'] : 0,
                            ':hidden' => (isset($config_option['hidden'])) ? $config_option['hidden'] : 0,
                        ));
                        if ($q) {
                            $option_id = $pdo->lastInsertId();
                        }
                    } else {
                        $option_id = $q->fetchObject()->id;
                    }

                    $q = $pdo->query("DELETE FROM tblpricing WHERE type = 'configoptions' AND relid IN ( SELECT * FROM ( SELECT id FROM tblproductconfigoptionssub WHERE configid = $option_id) AS subquery )");
                    $q = $pdo->query("DELETE FROM tblproductconfigoptionssub WHERE configid = $option_id");

                    $q = $pdo->prepare("INSERT INTO tblproductconfigoptionssub (configid, optionname, hidden) VALUES (:configid, :optionname, :hidden)");
                    foreach ($config_option['sub_options'] as $sub_option) {
                        $q->execute([
                            ':configid' => $option_id,
                            ':optionname' => (isset($sub_option['rawName'])) ? $sub_option['rawName'] : $sub_option['name'],
                            ':hidden' => (isset($sub_option['hidden'])) ? $sub_option['hidden'] : 0
                        ]);

                        $sub_option_id = $pdo->lastInsertId();
                        $q2 = $pdo->query("INSERT INTO tblpricing (id, type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, tsetupfee, monthly, quarterly, semiannually, annually, biennially, triennially) VALUES (NULL, 'configoptions', '1', $sub_option_id, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0')");
                    }
                }

                $q = $pdo->prepare("SELECT gid FROM tblproductconfiglinks WHERE gid = $config_group_id AND pid = ?");
                $q->execute(array($product_id));

                if ($q && $q->rowCount() < 1) {
                    $q = $pdo->prepare("INSERT INTO tblproductconfiglinks (gid, pid) VALUES (?, ?)");
                    $q->execute(array($config_group_id, $product_id));
                }

                // Create customfields
                $q = $pdo->query("SELECT id FROM tblcustomfields WHERE relid = $product_id AND fieldname = 'cchost|Hostname'");
                if ($q->rowCount() < 1) {
                    $q = $pdo->query("INSERT INTO tblcustomfields (type, relid, fieldname, fieldtype, description, fieldoptions, regexpr, adminonly, required, showorder, showinvoice, sortorder) VALUES ('product', $product_id, 'cchost|Hostname', 'text', 'Server FQDN', '', '', '', 'on', 'on', 'on', '0')");
                }

                cloudcone_respond(1, 'Configurable options set-up successful', array('reload' => true));
            }

        } else {
            cloudcone_respond(0, 'Invalid product ID_: '.$product_id);
        }
    } catch (Exception $e) {
        cloudcone_respond(0, $e->getMessage());
    }
}
