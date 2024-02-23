<?php
/*
 * vpn_openvpn_user_import.php
 *
 * part of CTECH packages for pfSense(R) software
 * Copyright (c) 2024 CTECH
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* Allow additional execution time 0 = no limit. */
ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');

require_once('globals.inc');
require_once('guiconfig.inc');
require_once('openvpn-user-import.inc');
require_once('pfsense-utils.inc');
require_once('certs.inc');
require_once('pkg-utils.inc');
require_once('classes/Form.class.php');

global $config;

$pgtitle = ['OpenVPN', 'User Import Utility'];
$input_errors = [];
$success_message = null;

if (!is_array($config['openvpn'])) {
    $config['openvpn'] = [];
}

if (!is_array($config['openvpn']['openvpn-server'])) {
    $config['openvpn']['openvpn-server'] = [];
}

$a_server = $config['openvpn']['openvpn-server'];

if (!is_array($config['system']['user'])) {
    $config['system']['user'] = [];
}

$a_user = $config['system']['user'];

if (!is_array($config['cert'])) {
    $config['cert'] = [];
}

$a_cert = $config['cert'];

$ras_server = [];

foreach ($a_server as $server) {
    if (isset($server['disable'])) {
        continue;
    }

    $vpnid = $server['vpnid'];

    if (stripos($server['mode'], 'server') === false) {
        continue;
    }

    $prot = $server['protocol'];
    $port = $server['local_port'];

    if ($server['description']) {
        $name = "{$server['description']} {$prot}:{$port}";
    } else {
        $name = "Server {$prot}:{$port}";
    }

    $ras_server[$vpnid] = [
        'index' => $vpnid,
        'name' => $name,
        'caref' => $server['caref'],
    ];
}

global $simplefields;

$simplefields = [
    'server',
    'email_domain',
    'network_domain',
    'validity',
];

init_config_arr(['installedpackages', 'vpn_openvpn_user_import', 'serverconfig', 'item']);
$openvpnexportcfg = &$config['installedpackages']['vpn_openvpn_user_import'];
$ovpnserverdefaults = &$openvpnexportcfg['serverconfig']['item'];
init_config_arr(['installedpackages', 'vpn_openvpn_user_import', 'defaultsettings']);
$cfg = &$config['installedpackages']['vpn_openvpn_user_import']['defaultsettings'];

if (!is_array($ovpnserverdefaults)) {
    $ovpnserverdefaults = [];
}

if (isset($_POST['save'])) {
    $vpnid = $_POST['server'];
    $index = count($ovpnserverdefaults);
    $server = $ras_server[$vpnid];

    foreach ($ovpnserverdefaults as $key => $cfg) {
        if ($cfg['server'] == $vpnid) {
            $index = $key;
            break;
        }
    }

    $cfg = &$ovpnserverdefaults[$index];

    if (!is_array($cfg)) {
        $cfg = [];
    }

    if (!$server) {
        $input_errors[] = 'Server invalid.';
    }

    foreach ($simplefields as $value) {
        $cfg[$value] = $_POST[$value];
    }

    if (!$_FILES['csv_file'] || !$_FILES['csv_file']['tmp_name'] || !file_exists($_FILES['csv_file']['tmp_name'])) {
        $input_errors[] = 'File not sent.';
    } elseif ($server) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'rb');
        $index = 0;
        $ca = $server['caref'];
        $emailDomain = $cfg['email_domain'];
        $networkDomain = $cfg['network_domain'];
        $validity = (int)$cfg['validity'];

        while (($line = fgetcsv($file)) !== false) {
            if ($index === 0) {
                $index++;
                continue;
            }

            $user = $line[0];

            $cert = [
                'refid' => uniqid(),
                'descr' => $user,
                'caref' => $cert,
                'type' => $user,
            ];

            $openSslCreate = cert_create(
                $cert,
                $ca,
                2048,
                $validity,
                [
                    'commonName' => $user,
                    'countryName' => 'BR',
                    'stateOrProvinceName' => 'Sao Paulo',
                    'localityName' => 'Sao Paulo',
                    'organizationName' => $networkDomain,
                    'subjectAltName' => implode(',', [
                        "email:{$user}@{$emailDomain}",
                        "DNS:{$user}",
                    ]),
                ],
                'user',
                'sha256',
                'RSA',
                null
            );

            if (!$openSslCreate) {
                $input_errors[] = "Failed to create certificate to user '{$user}'";
                $index++;
                continue;
            }

            $config['cert'][] = $cert;

            $index++;
        }

        fclose($file);
    }

    if (empty($input_errors)) {
        write_config('Added certificate via User Import');

        $success_message = 'Certificates generated successfully.';
    }
}

include('head.inc');

if (!empty($input_errors)) {
    print_input_errors($input_errors);
}

if (!empty($success_message)) {
    print_info_box($success_message, 'success');
}

$tab_array = [];
$tab_array[] = [gettext('Server'), false, 'vpn_openvpn_server.php'];
$tab_array[] = [gettext('Client'), false, 'vpn_openvpn_client.php'];
$tab_array[] = [gettext('Client Specific Overrides'), false, 'vpn_openvpn_csc.php'];
$tab_array[] = [gettext('Wizards'), false, 'wizard.php?xml=openvpn_wizard.xml'];
add_package_tabs('OpenVPN', $tab_array);
display_top_tabs($tab_array);

$form = new Form('Import users');
$form->setMultipartEncoding();

$section = new Form_Section('OpenVPN Server');

$serverlist = [];
foreach ($ras_server as $server) {
    $serverlist[$server['index']] = $server['name'];
}

$section->addInput(
    new Form_Select(
        'server', 'Remote Access Server', $cfg['server'], $serverlist
    )
);

$form->add($section);

$section = new Form_Section('User Configuration Behavior');

$section->addInput(
    new Form_Input(
        'email_domain', 'Email domain', 'text', $cfg['email_domain']
    )
)->setHelp('Enter the email domain the user.');

$section->addInput(
    new Form_Input(
        'network_domain', 'Network domain', 'text', $cfg['network_domain']
    )
)->setHelp('Enter the network domain the user will use to connect to this VPN.');

$section->addInput(
    new Form_Input(
        'validity', 'Validity (days)', 'number', $cfg['validity']
    )
)->setHelp('Enter the certificate validity.');

$section->addInput(
    new Form_Input(
        'csv_file', 'CSV file', 'file', null
    )
)->setHelp('Enter the CSV file with the usernames.');

$form->add($section);

print($form);
?>

<script type="text/javascript">
    //<![CDATA[
    serverdefaults = <?= json_encode($ovpnserverdefaults) ?>;

    function server_changed() {
        function setFieldValue(field, value) {
            let checkboxes = $('input[type=checkbox]#' + field);
            checkboxes.prop('checked', value == 'yes').trigger('change');

            let inputboxes = $('input[type!=checkbox]#' + field);
            inputboxes.val(value);

            let selectboxes = $('select#' + field);
            selectboxes.val(value);

            let textareaboxes = $('textarea#' + field);
            textareaboxes.val(value);
        }

        const index = document.getElementById('server').value;

        let fields, fieldnames, fieldname;

        for (let i = 0; i < serverdefaults.length; i++) {
            if (serverdefaults[i]['server'] != index) {
                continue;
            }

            fields = serverdefaults[i];
            fieldnames = Object.getOwnPropertyNames(fields);

            for (let fieldnr = 0; fieldnr < fieldnames.length; fieldnr++) {
                fieldname = fieldnames[fieldnr];
                setFieldValue(fieldname, fields[fieldname]);
            }

            break;
        }
    }

    events.push(function () {
        // ---------- OnChange handlers ---------------------------------------------------------

        $('#server').on('change', function () {
            server_changed();
        });

        // ---------- On initial page load ------------------------------------------------------------

        server_changed();
    });
    //]]>
</script>

<?php

include('foot.inc');
