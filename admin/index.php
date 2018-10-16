<?php
$config = json_decode(file_get_contents('/home/pi/tmp/config'), true);
$lang = 0;

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $accept) {
        $tokens = explode(';', $accept);
        $name = strtolower(trim(array_shift($tokens)));

        switch ($name) {
            case 'zh-tw':
            case 'zh-hant':
            case 'zh-hant-tw':
                $lang = 1;
                break;
        }

        break;
    }
}

switch ($lang) {
    case 1:
        $msg = array(
            'rotate'      => '螢幕旋轉',
            'resolution'  => '螢幕解析度',
            'auto'        => '自動偵測',
            'network'     => '網路',
            'wire'        => '有線網路',
            'dhcp'        => '使用 DHCP 取得',
            'static'      => '手動設定 IP',
            'ip'          => 'IP 位址',
            'mask'        => '子網路遮罩',
            'gateway'     => '路由器',
            'dns'         => 'DNS 伺服器',
            'wireless'    => '無線網路',
            'ssid'        => '無線網路名稱',
            'password'    => '無線網路密碼',
            'ap'          => '組態設定連線',
            'ap_ssid'     => 'Qboard 連線名稱',
            'ap_password' => '密碼',
            'visible'     => '是否公開搜尋',
            'show'        => '顯示',
            'hide'        => '隱藏',
            'routing'     => '路由線路',
            'backend'     => 'Qboard Cloud 後台',
            'domain'      => '網域名稱',
            'port'        => '連接埠',
            'save'        => '儲存',
        );
        break;
    default:
        $msg = array(
            'rotate'      => 'Screen Rotate',
            'resolution'  => 'Screen Resolution',
            'auto'        => 'Auto',
            'network'     => 'Network',
            'wire'        => 'Wire',
            'dhcp'        => 'DHCP',
            'static'      => 'Static IP',
            'ip'          => 'IP Address',
            'mask'        => 'Subnet Mask',
            'gateway'     => 'Gateway',
            'dns'         => 'DNS Server',
            'wireless'    => 'Wireless',
            'ssid'        => 'SSID',
            'password'    => 'Password',
            'ap'          => 'Configure AP',
            'ap_ssid'     => 'SSID',
            'ap_password' => 'Password',
            'show'        => 'Show',
            'hide'        => 'Hide',
            'routing'     => 'Routing Via',
            'backend'     => 'Qboard Backend',
            'domain'      => 'Domain',
            'port'        => 'Port',
            'save'        => 'Save',
        );
}

?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <link href="index.css" rel="stylesheet" type="text/css" />
    <script src="assets/jquery.js"></script>
    <script src="assets/loadingoverlay.js"></script>
</head>
<body>
<form>
    <div class="header"><img src="images/logo.svg"></div>
    <div class="main">
<?php if ($config['ap_ssid'] === 'AP') { ?>
        <div class="title"><img src="images/ap.svg"><?= $msg['ap'] ?></div>
        <div class="ap">
            <div class="field"><?= $msg['ap_ssid'] ?></div>
            <input class="input" type="text" name="ap_ssid" required>
        </div>
<?php } else { ?>
        <div class="title"><img src="images/rotate.svg"><?= $msg['rotate'] ?></div>
        <div class="rotate">
            <label class="container">0&deg;<input type="radio" name="rotate" value="0"<?= $config['rotate'] === '0' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container">90&deg;<input type="radio" name="rotate" value="1"<?= $config['rotate'] === '1' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container">180&deg;<input type="radio" name="rotate" value="2"<?= $config['rotate'] === '2' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container">270&deg;<input type="radio" name="rotate" value="3"<?= $config['rotate'] === '3' ? ' checked' : '' ?>><span class="checkmark"></span></label>
        </div>
        <hr>
        <div class="title"><img src="images/resolution.svg"><?= $msg['resolution'] ?></div>
        <div class="resolution">
            <label class="container">720p<input type="radio" name="resolution" value="1"<?= $config['resolution'] === '1' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container">1080p<input type="radio" name="resolution" value="2"<?= $config['resolution'] === '2' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container"><?= $msg['auto'] ?><input type="radio" name="resolution" value="0"<?= $config['resolution'] === '0' ? ' checked' : '' ?>><span class="checkmark"></span></label>
        </div>
        <hr>
        <div class="title"><img src="images/network.svg"><?= $msg['network'] ?></div>
        <div class="sub-title"><?= $msg['wire'] ?></div>
        <div class="eth0">
            <label class="container"><?= $msg['dhcp'] ?><input data-target="eth0-cfg" type="radio" name="eth0" value="dhcp"<?= $config['eth0'] === 'dhcp' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container"><?= $msg['static'] ?><input data-target="eth0-cfg" type="radio" name="eth0" value="static"<?= $config['eth0'] === 'static' ? ' checked' : '' ?>><span class="checkmark"></span></label>
        </div>
        <div class="eth0-cfg<?= $config['eth0'] === 'dhcp' ? ' hide' : '' ?>">
<?php
    if ($config['eth0_address']) {
        $address = explode('.', $config['eth0_address']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['ip'] ?></div>
            <input type="hidden" name="eth0_address" value="<?= $config['eth0_address'] ?>">
            <div>
                <input class="input ip req" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[3] ?>">
            </div>
<?php
    if ($config['eth0_netmask']) {
        $address = explode('.', $config['eth0_netmask']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['mask'] ?></div>
            <input type="hidden" name="eth0_netmask" value="<?= $config['eth0_netmask'] ?>">
            <div>
                <input class="input ip req" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[3] ?>">
            </div>
<?php
    if ($config['eth0_router']) {
        $address = explode('.', $config['eth0_router']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['gateway'] ?></div>
            <input type="hidden" name="eth0_router" value="<?= $config['eth0_router'] ?>">
            <div>
                <input class="input ip" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[3] ?>">
            </div>
<?php
    if ($config['eth0_name_server']) {
        $address = explode('.', $config['eth0_name_server']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['dns'] ?></div>
            <input type="hidden" name="eth0_name_server" value="<?= $config['eth0_name_server'] ?>">
            <div>
                <input class="input ip" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[3] ?>">
            </div>
        </div>
        <div class="sub-title"><?= $msg['wireless'] ?></div>
        <div class="wan0">
            <div class="field"><?= $msg['ssid'] ?></div>
            <input class="input" type="text" name="wifi_ssid" value="<?= $config['wifi_ssid'] ?>">
            <div class="field"><?= $msg['password'] ?></div>
            <input class="input" type="text" name="wifi_password" value="<?= $config['wifi_password'] ?>">
            <label class="container"><?= $msg['dhcp'] ?><input data-target="wan0-cfg" type="radio" name="wifi" value="dhcp"<?= $config['wifi'] === 'dhcp' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container"><?= $msg['static'] ?><input data-target="wan0-cfg" type="radio" name="wifi" value="static"<?= $config['wifi'] === 'static' ? ' checked' : '' ?>><span class="checkmark"></span></label>
        </div>
        <div class="wan0-cfg<?= $config['wifi'] === 'dhcp' ? ' hide' : '' ?>">
<?php
    if ($config['wifi_address']) {
        $address = explode('.', $config['wifi_address']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['ip'] ?></div>
            <input type="hidden" name="wifi_address" value="<?= $config['wifi_address'] ?>">
            <div>
                <input class="input ip req" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[3] ?>">
            </div>
<?php
    if ($config['wifi_netmask']) {
        $address = explode('.', $config['wifi_netmask']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['mask'] ?></div>
            <input type="hidden" name="wifi_netmask" value="<?= $config['wifi_netmask'] ?>">
            <div>
                <input class="input ip req" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip req" type="text" value="<?= $address[3] ?>">
            </div>
<?php
    if ($config['wifi_router']) {
        $address = explode('.', $config['wifi_router']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['gateway'] ?></div>
            <input type="hidden" name="wifi_router" value="<?= $config['wifi_router'] ?>">
            <div>
                <input class="input ip" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[3] ?>">
            </div>
<?php
    if ($config['wifi_name_server']) {
        $address = explode('.', $config['wifi_name_server']);
    } else {
        $address = array('', '', '', '');
    }
?>
            <div class="sub-field"><?= $msg['dns'] ?></div>
            <input type="hidden" name="wifi_name_server" value="<?= $config['wifi_name_server'] ?>">
            <div>
                <input class="input ip" type="text" value="<?= $address[0] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[1] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[2] ?>">
                <span class="dot">.</span>
                <input class="input ip" type="text" value="<?= $address[3] ?>">
            </div>
        </div>
        <hr>
        <div class="title"><img src="images/ap.svg"><?= $msg['ap'] ?></div>
        <div class="ap">
            <div class="field"><?= $msg['ap_ssid'] ?></div>
            <input class="input disabled" type="text" value="QB-<?= $config['ap_ssid'] ?>" disabled>
            <div class="field"><?= $msg['ap_password'] ?></div>
            <input class="input" type="text" name="ap_password" value="<?= $config['ap_password'] ?>" required>
            <div class="field"><?= $msg['visible'] ?></div>
            <label class="container"><?= $msg['show'] ?><input type="radio" name="ap_invisible" value="0"<?= $config['ap_invisible'] === '0' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container"><?= $msg['hide'] ?><input type="radio" name="ap_invisible" value="1"<?= $config['ap_invisible'] === '1' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <div class="field"><?= $msg['routing'] ?></div>
            <label class="container"><?= $msg['wire'] ?><input type="radio" name="network" value="wired"<?= $config['network'] === 'wired' ? ' checked' : '' ?>><span class="checkmark"></span></label>
            <label class="container"><?= $msg['wireless'] ?><input type="radio" name="network" value="wireless"<?= $config['network'] === 'wireless' ? ' checked' : '' ?>><span class="checkmark"></span></label>
        </div>
        <hr>
        <div class="title"><img src="images/backend.svg"><?= $msg['backend'] ?></div>
        <div>
            <div class="field"><?= $msg['domain'] ?></div>
            <input class="input" type="text" name="domain" value="<?= $config['domain'] ?>" required>
            <div class="field"><?= $msg['port'] ?></div>
            <input class="input" type="text" name="port" value="<?= $config['port'] ?>" required>
        </div>
<?php } ?>
        <div class="footer">©2018 Qbaord</div>
        <div></div>
    </div>
    <button class="submit" type="submit"><?= $msg['save'] ?></button>
</form>
<script src="index.js"></script>
</body>
</html>
