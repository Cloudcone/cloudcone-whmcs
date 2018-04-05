<?php
// prevent direct access
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

function cloudcone_respond($status, $message, $data = false) {
    header('Content-Type: application/json');
    die(json_encode(array(
        'status' => $status,
        'message' => $message,
        '__data' => $data
    )));
}

/**
 * Load language file
 */
function cloudcone_load_language() {
    global $_LANG, $CONFIG;

    $langDir                = __DIR__ . '/lang/';
    $availableLangsFullPath = glob( $langDir . '*.php' );
    $availableLangs         = array();
    foreach ( $availableLangsFullPath as $availableLang ) {
        $availableLangs[] = strtolower( basename( $availableLang ) );
    }

    if ( empty( $lang ) ) {
        if ( isset( $_SESSION['Language'] ) ) {
            $language = $_SESSION['Language'];
        } else if ( isset( $_SESSION['adminlang'] ) ) {
            $language = $_SESSION['adminlang'];
        } else {
            $language = $CONFIG['Language'];
        }
    } else {
        $language = $lang;
    }

    $language = strtolower( $language ) . '.php';

    if ( ! in_array( $language, $availableLangs ) ) {
        $language = 'english.php';
    }
    require_once( $langDir . $language );
}
cloudcone_load_language();

/**
 * Get scripts and styles for the product config page
 *
 * @return string
 */
function cloudcone_get_product_scripts() {
	global $_LANG;

    $css = "";

	$js = "<script>
    function getQueryParam(param) {
        location.search.substr(1)
            .split(\"&\")
            .some(function(item) { // returns first occurence and stops
                return item.split(\"=\")[0] == param && (param = item.split(\"=\")[1])
            })
        return param
    }

    $('.module-settings tr .fieldlabel:contains(\"Configurable Options\") + .fieldarea input[type=text]').remove();
	$('.module-settings tr .fieldlabel:contains(\"Configurable Options\") + .fieldarea').prepend('<a href=\"#\"id=\"ccone-generate-config\" class=\"btn btn-info\">Generate</a>');

	$('form[name=\"packagefrm\"] div.tab-content div#tab3').on('click', 'a#ccone-generate-config', function (e) {
		$.post(\"../modules/servers/cloudcone/actions.php\", {productID: getQueryParam('id'), cloudcone_action: 'cloudcone_configure'}, function (data) {
            if (data.__data.reload !== undefined) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
        e.preventDefault();
    });
	</script>";
    return $css.$js;
}

/**
 * Get CloudCone instance ID from a given service ID
 */
function cloudcone_get_instanceid($serviceid) {
    $pdo = Capsule::connection()->getPdo();
    $q = $pdo->prepare("SELECT instanceid FROM mod_cloudcone WHERE hostingid = ?");
    $q->execute(array($serviceid));

    if ($q && $q->rowCount() > 0) {
        return $q->fetchObject()->instanceid;
    } else {
        return false;
    }
}

/**
 * Insert or update the CloudCone instance ID for a given service ID
 */
function cloudcone_set_instanceid($serviceid, $instanceid) {
    $pdo = Capsule::connection()->getPdo();
    $pdo->beginTransaction();

    try {
        $q = $pdo->prepare("INSERT INTO mod_cloudcone
                                (hostingid, instanceid)
                            VALUES
                                (?, ?)
                            ON DUPLICATE KEY UPDATE
                                instanceid = VALUES (instanceid)");

        $q->execute(array($serviceid, $instanceid));
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollback();
    }
}

function cloudcone_get_service_params($serviceid, $uid) {
    $pdo = Capsule::connection()->getPdo();
    $q = $pdo->prepare("SELECT tblservers.username, tblservers.password FROM tblservers, tblhosting WHERE tblhosting.id = $serviceid AND tblhosting.userid = $uid AND tblhosting.server = tblservers.id");
    $q->execute();
    if ($q && $q->rowCount() > 0) {
        $params = $q->fetchObject();
        return array(
            'username' => $params->username,
            'password' => decrypt($params->password)
        );
    }
}

function cloudcone_get_config_options() {
    $ch = curl_init('https://app.cloudcone.com/webhook/whmcs');
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
