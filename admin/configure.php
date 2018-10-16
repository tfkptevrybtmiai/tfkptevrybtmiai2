<?php

function diff($array, &$default, ...$names) {
    $change = false;

    foreach ($names as $name) {
        if (key_exists($name, $array)) {
            $value = trim("{$array[$name]}");

            if (!key_exists($name, $default) || $value !== $default[$name]) {
                $default[$name] = $value;

                $change = true;
            }
        }
    }

    return $change;
}

function mask2cidr($mask) {
    $mask = explode('.', $mask);
    $bits = 0;

    foreach ($mask as $octect) {
        $bin = decbin($octect);
        $bin = str_replace('0', '', $bin);
        $bits = $bits + strlen($bin);
    }

    return $bits;
}

//--

$input = json_decode(file_get_contents('php://input'), true);
$config = json_decode(file_get_contents('/home/pi/tmp/config'), true);
$command = '';
$error = 0;
$reboot = 'reboot';

//--

if ($config['ap_ssid'] === 'AP') {
    if (diff($input, $config, 'ap_ssid')) {
        if (empty($config['token'])) {
            $error = 'DEVICE_NOT_READY';
        } else {
            $result = json_decode(file_get_contents("http://127.0.0.1:8000/setup-device?id={$config['ap_ssid']}"), true);

            if (empty($result)) {
                $error = 'SERVICE_NOT_READY';
            } else if (empty($result['success'])) {
                $error = 'SETUP_ERROR';
            } else {
                $template = file_get_contents('/home/pi/client/bin/hostapd.conf.template');
                $content = str_replace('#=SSID_WPA_PASSPHRASE=#', "ssid=QB-{$config['ap_ssid']}\nwpa_passphrase={$config['ap_password']}\nignore_broadcast_ssid={$config['ap_invisible']}", $template);

                file_put_contents('/home/pi/tmp/hostapd.conf', $content);

                $config['code'] = $result['device']['qr_code'];

                $command = "{$command}cp /home/pi/tmp/config /home/pi/tmp/config.default\nsudo cp /home/pi/tmp/hostapd.conf /etc/hostapd/\n";
                $reboot = 'shutdown -h now';
            }
        }
    }
} else {
    $reset = false;

    if (!empty($config['reset'])) {
        $config = json_decode(file_get_contents('/home/pi/tmp/config.default'), true);
        $reset = true;
    }

    //--

    if ($reset || diff($input, $config, 'rotate', 'resolution')) {
        $template = file_get_contents('/home/pi/client/bin/config.txt.template');

        $info = "display_hdmi_rotate={$config['rotate']}";

        switch ($config['resolution']) {
            case '1':
                $info = "{$info}\nhdmi_group=2\nhdmi_mode=85";
                break;
            case '2':
                $info = "{$info}\nhdmi_group=2\nhdmi_mode=82";
                break;
        }

        $content = str_replace('#=DISPLAY_HDMI_ROTATE=#', $info, $template);

        file_put_contents('/home/pi/tmp/config.txt', $content);

        $command = "{$command}sudo cp /home/pi/tmp/config.txt /boot/\n";
    }

    //--

    if ($reset || diff($input, $config, 'eth0', 'eth0_address', 'eth0_netmask', 'eth0_router', 'eth0_name_server', 'wifi', 'wifi_address', 'wifi_netmask', 'wifi_router', 'wifi_name_server')) {
        $content = file_get_contents('/home/pi/client/bin/dhcpcd.conf.template');

        if ($config['eth0'] === 'static') {
            $info = "interface eth0\n    static ip_address={$config['eth0_address']}/" . mask2cidr($config['eth0_netmask']);

            if ($config['eth0_router']) {
                $info = "{$info}\n    routers={$config['eth0_router']}";
            }

            if ($config['eth0_name_server']) {
                $info = "{$info}\n    domain_name_servers={$config['eth0_name_server']}";
            }

            $content = str_replace('#=INTERFACE_ETH0=#', $info, $content);
        }

        if ($config['wifi'] === 'static') {
            $info = "interface wlan1\n    static ip_address={$config['wifi_address']}/" . mask2cidr($config['wifi_netmask']);

            if ($config['wifi_router']) {
                $info = "{$info}\n    routers={$config['wifi_router']}";
            }

            if ($config['wifi_name_server']) {
                $info = "{$info}\n    domain_name_servers={$config['wifi_name_server']}";
            }

            $content = str_replace('#=INTERFACE_WLAN1=#', $info, $content);
        }

        file_put_contents('/home/pi/tmp/dhcpcd.conf', $content);

        $command = "{$command}sudo cp /home/pi/tmp/dhcpcd.conf /etc/\n";
    }

    //--

    if ($reset || diff($input, $config, 'wifi_ssid', 'wifi_password')) {
        $template = file_get_contents('/home/pi/client/bin/wpa_supplicant.conf.template');
        $content = str_replace('#=SSID_PSK=#', "ssid=\"{$config['wifi_ssid']}\"\n    psk=\"{$config['wifi_password']}\"", $template);

        file_put_contents('/home/pi/tmp/wpa_supplicant.conf', $content);

        $command = "{$command}sudo cp /home/pi/tmp/wpa_supplicant.conf /etc/wpa_supplicant/\n";
    }

    //--

    if ($reset || diff($input, $config, 'ap_password', 'ap_invisible')) {
        $template = file_get_contents('/home/pi/client/bin/hostapd.conf.template');
        $content = str_replace('#=SSID_WPA_PASSPHRASE=#', "ssid=QB-{$config['ap_ssid']}\nwpa_passphrase={$config['ap_password']}\nignore_broadcast_ssid={$config['ap_invisible']}", $template);

        file_put_contents('/home/pi/tmp/hostapd.conf', $content);

        $command = "{$command}sudo cp /home/pi/tmp/hostapd.conf /etc/hostapd/\n";
    }

    //--

    if ($reset || diff($input, $config, 'network')) {
        $device = $config['network'] === 'wired' ? 'eth0' : 'wlan1';

        file_put_contents('/home/pi/tmp/nat.sh', "sudo iptables -t nat -A POSTROUTING -o {$device} -j MASQUERADE");

        $command = "{$command}\n";
    }

    //--

    if ($reset || diff($input, $config, 'domain', 'port')) {
        $command = "{$command}\n";
    }
}

//--

header('Content-Type: application/json; charset=UTF-8');

if ($command) {
    file_put_contents('/home/pi/tmp/config', json_encode($config, JSON_PRETTY_PRINT));
    file_put_contents('/home/pi/tmp/command.sh', "{$command}sudo {$reboot}\n");

    echo '{}';
} else {
    echo json_encode(array('error' => $error ?: 'NOT_MODIFIED'));
}
