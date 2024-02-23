<?php
/*
 * openvpn-user-import.inc
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

require_once('globals.inc');
require_once('util.inc');
require_once('pfsense-utils.inc');

function openvpn_user_import_install_command()
{
    global $config;

    init_config_arr(['installedpackages', 'vpn_openvpn_user_import', 'defaultsettings']);

    $configChanged = false;

    if (!array_key_exists('email_domain', $config['installedpackages']['vpn_openvpn_user_import']['defaultsettings'])) {
        $config['installedpackages']['vpn_openvpn_user_import']['defaultsettings']['email_domain'] = $config['system']['domain'];
        $configChanged = true;
    }

    if (!array_key_exists('network_domain', $config['installedpackages']['vpn_openvpn_user_import']['defaultsettings'])) {
        $config['installedpackages']['vpn_openvpn_user_import']['defaultsettings']['network_domain'] = $config['system']['domain'];
        $configChanged = true;
    }

    if (!array_key_exists('validity', $config['installedpackages']['vpn_openvpn_user_import']['defaultsettings'])) {
        $config['installedpackages']['vpn_openvpn_user_import']['defaultsettings']['validity'] = 365;
        $configChanged = true;
    }

    if ($configChanged) {
        write_config('[OpenVPN User Import] Package configuration updated.');
    }
}
