<?php
require_once '../../../init.php';
require_once 'loader.php';

$ca = new WHMCS_ClientArea();
if (!$ca->isLoggedIn()) {
    if ((!isset($_SESSION['adminid']) || ((int)$_SESSION['adminid'] <= 0))) {
        die('<div class="alert alert-danger">' . $_LANG['ccone']['unauthorized'] . '</div></body>');
    }
    $uid = (int)$_GET['uid'];
} else {
    $uid = $ca->getUserID();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $serviceid = $_GET['id'];
} else {
    die('<div class="alert alert-danger">' . $_LANG['ccone']['unauthorized'] . '</div></body>');
}

$params = cloudcone_get_service_params($serviceid, $uid);
$instanceid = cloudcone_get_instanceid($serviceid);
$cloudcone = new CloudConeAPI($params['username'], $params['password']);
$vnc_data = json_decode($cloudcone->computeVNC($instanceid), true);
$vnc_data = $vnc_data['__data'];
?>
<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Console</title>

    <!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame
                Remove this if you use the .htaccess -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <!-- Apple iOS Safari settings -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link href="../../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="../../../templates/six/css/overrides.css" rel="stylesheet">
    <link href="../../../templates/six/css/styles.css" rel="stylesheet">

    <link rel="stylesheet" href="novnc/base.css" title="plain">
    <link href="css/vnc.css" rel="stylesheet">

    <script src="novnc/util.js"></script>
    <script type="text/javascript" src="novnc/webutil.js"></script>
    <script type="text/javascript" src="novnc/base64.js"></script>
    <script type="text/javascript" src="novnc/websock.js"></script>
    <script type="text/javascript" src="novnc/des.js"></script>
    <script type="text/javascript" src="novnc/keysymdef.js"></script>
    <script type="text/javascript" src="novnc/keyboard.js"></script>
    <script type="text/javascript" src="novnc/input.js"></script>
    <script type="text/javascript" src="novnc/display.js"></script>
    <script type="text/javascript" src="novnc/inflator.js"></script>
    <script type="text/javascript" src="novnc/rfb.js"></script>
    <script type="text/javascript" src="novnc/keysym.js"></script>
</head>

<body style="margin: 0px; background-image: none; border-radius: 8px;">
<div id="noVNC_screen">
    <div id="noVNC_status_bar" class="" style="margin-top: 0px;padding: 3px;">
        <table border="0" width="100%"><tbody><tr>
                <td><div id="noVNC_status" style="position: relative; height: auto;"></div></td>
                <td style="width: 250px"><div id="noVNC_buttons">
                    <input type="button" value="Send Ctrl + Alt + Del" id="sendCtrlAltDelButton" class="btn btn-danger btn-xs btn-fill pull-right" style="display: inline;">
                    <span id="noVNC_xvp_buttons" style="display: none;">
                    <input type="button" value="Shutdown" id="xvpShutdownButton">
                    <input type="button" value="Reboot" id="xvpRebootButton">
                    <input type="button" value="Reset" id="xvpResetButton">
                    </span>
                    </div></td>
            </tr></tbody></table>
    </div>
    <canvas id="noVNC_canvas">
        Canvas not supported.
    </canvas>
</div>

        <script>
        /*jslint white: false */
        /*global window, $, Util, RFB, */
        "use strict";

        // Load supporting scripts


        var rfb;

        function passwordRequired(rfb) {
            var msg;
            msg = '<form onsubmit="return setPassword();"';
            msg += '  style="margin-bottom: 0px">';
            msg += 'Password Required: ';
            msg += '<input type=password size=10 id="password_input" class="noVNC_status">';
            msg += '<\/form>';
            $D('noVNC_status_bar').setAttribute("class", "noVNC_status_warn");
            $D('noVNC_status').innerHTML = msg;
        }
        function setPassword() {
            rfb.sendPassword($D('password_input').value);
            return false;
        }
        function sendCtrlAltDel() {
            rfb.sendCtrlAltDel();
            return false;
        }
        function xvpShutdown() {
            rfb.xvpShutdown();
            return false;
        }
        function xvpReboot() {
            rfb.xvpReboot();
            return false;
        }
        function xvpReset() {
            rfb.xvpReset();
            return false;
        }

        function updateState(rfb, state, oldstate, msg) {
            var s, sb, cad, level;
            s = $D('noVNC_status');
            sb = $D('noVNC_status_bar');
            cad = $D('sendCtrlAltDelButton');
            switch (state) {
                case 'failed':       level = "alert alert-danger";  break;
                case 'fatal':        level = "alert alert-danger";  break;
                case 'normal':       level = "alert alert-info"; break;
                case 'disconnected': level = "alert alert-info"; break;
                case 'loaded':       level = "alert alert-info"; break;
                default:             level = "alert alert-warning";   break;
            }

            if (state === "normal") {
                cad.disabled = false;
            } else {
                cad.disabled = true;
                xvpInit(0);
            }

            if (typeof(msg) !== 'undefined') {
                sb.setAttribute("class", "noVNC_status_" + level);
                s.innerHTML = msg;
            }
        }

        function xvpInit(ver) {
            var xvpbuttons;
            xvpbuttons = $D('noVNC_xvp_buttons');
            if (ver >= 1) {
                xvpbuttons.style.display = 'inline';
            } else {
                xvpbuttons.style.display = 'none';
            }
        }

        window.onscriptsload = function () {
            var host, port, password, path, token;

            $D('sendCtrlAltDelButton').style.display = "inline";
            $D('sendCtrlAltDelButton').onclick = sendCtrlAltDel;
            $D('xvpShutdownButton').onclick = xvpShutdown;
            $D('xvpRebootButton').onclick = xvpReboot;
            $D('xvpResetButton').onclick = xvpReset;

            WebUtil.init_logging(WebUtil.getQueryVar('logging', 'warn'));
            document.title = unescape(WebUtil.getQueryVar('title', 'noVNC'));
            // By default, use the host and port of server that served this file
            host = WebUtil.getQueryVar('host', window.location.hostname);
            port = WebUtil.getQueryVar('port', window.location.port);

            // if port == 80 (or 443) then it won't be present and should be
            // set manually
            if (!port) {
                if (window.location.protocol.substring(0,5) == 'https') {
                    port = 443;
                }
                else if (window.location.protocol.substring(0,4) == 'http') {
                    port = 80;
                }
            }

            // If a token variable is passed in, set the parameter in a cookie.
            // This is used by nova-novncproxy.
            token = WebUtil.getQueryVar('token', null);
            if (token) {
                WebUtil.createCookie('token', token, 1)
            }

            password = WebUtil.getQueryVar('password', '');
            path = WebUtil.getQueryVar('path', 'websockify');

            if ((!host) || (!port)) {
                updateState('failed',
                    "Must specify host and port in URL");
                return;
            }

            rfb = new RFB({'target':       $D('noVNC_canvas'),
                           'encrypt':      WebUtil.getQueryVar('encrypt',1),
                           'repeaterID':   WebUtil.getQueryVar('repeaterID', ''),
                           'true_color':   WebUtil.getQueryVar('true_color', true),
                           'local_cursor': WebUtil.getQueryVar('cursor', true),
                           'shared':       WebUtil.getQueryVar('shared', true),
                           'view_only':    WebUtil.getQueryVar('view_only', false),
                           'onUpdateState':  updateState,
                           'onXvpInit':    xvpInit,
                           'onPasswordRequired':  passwordRequired
                       });
            rfb.connect('<? echo $vnc_data['socket_host'] ?>', '<? echo $vnc_data['socket_port'] ?>', '<? echo $vnc_data['socket_pass'] ?>', '?token=<? echo $vnc_data['socket_hash'] ?>');
        };
        </script>
<script>
Util.load_scripts(["webutil.js", "base64.js", "websock.js", "des.js",
"keysymdef.js", "keyboard.js", "input.js", "display.js",
"inflator.js", "rfb.js", "keysym.js"]);
</script>


</body></html>
