<?php
class CloudConeAPI {
    private $base_url = 'https://api.cloudcone.com/api/v1';
    private $api_key;
    private $api_hash;

    public function __construct($api_key, $api_hash) {
        $this->api_key = $api_key;
        $this->api_hash = $api_hash;
    }

    private function sendRequest($path, $type, $params = array(), $errors = true) {
        $ch = curl_init($this->base_url.$path);
        if ($type === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } else if ($type === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else if ($type === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'App-Secret: '.$this->api_key,
            'Hash: '.$this->api_hash
        ));

        $return = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($return['status']) && $return['status'] > 0) {
            return json_encode($return);
        } else {
            if ($errors) {
                throw new Exception($return['message']);
            } else {
                return false;
            }
        }
    }

    public function computeCreate($hostname, $cpu, $ram, $disk, $ips, $os, $ssd, $pvtnet, $ipv6, $plan = '', $node = '') {
        $plan = (empty(trim($plan))) ? NULL : $plan;
        $node = (empty(trim($node))) ? '0' : $node;

        return $this->sendRequest(
            '/compute/create',
            'POST',
            array(
                'hostname' => $hostname,
                'cpu' => $cpu,
                'ram' => $ram,
                'disk' => $disk,
                'ips' => $ips,
                'os' => $os,
                'ssd' => $ssd,
                'pvtnet' => $pvtnet,
                'ipv6' => $ipv6,
                'plan' => $plan,
                'node' => $node
            )
        );
    }

    public function computeResize($instanceid, $cpu, $ram, $disk) {
        return $this->sendRequest(
            "/compute/$instanceid/resize",
            'POST',
            array(
                'cpu' => $cpu,
                'ram' => $ram,
                'disk' => $disk
            )
        );
    }

    public function computeShutdown($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/shutdown",
            'GET'
        );
    }

    public function computeBoot($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/boot",
            'GET'
        );
    }

    public function computeReboot($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/reboot",
            'GET'
        );
    }

    public function computeDestroy($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/destroy",
            'GET'
        );
    }

    public function computeResetPassword($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/reset/pass",
            'POST',
            array(
                'password' => 'not_used',
                'reboot' => 'true'
            )
        );
    }

    public function computeReinstall($instanceid, $os) {
        return $this->sendRequest(
            "/compute/$instanceid/reinstall",
            'POST',
            array(
                'os' => $os,
            )
        );
    }

    public function computeInfo($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/info",
            'GET'
        );
    }

    public function computeGraphs($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/graphs",
            'GET'
        );
    }

    public function computeVNC($instanceid) {
        return $this->sendRequest(
            "/compute/$instanceid/vnc",
            'GET'
        );
    }

    public function dedicatedHypervisors() {
        return $this->sendRequest(
            "/dedicated/hypervisors",
            'GET'
        );
    }
}
