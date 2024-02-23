<?php
/*
 * openvpnuserimport.inc
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

global $priv_list;

$priv_list['page-openvpn-user-import'] = [];
$priv_list['page-openvpn-user-import']['name'] = 'WebCfg - OpenVPN: User Import Utility';
$priv_list['page-openvpn-user-import']['descr'] = 'Allow access to the OpenVPN: User Import Utility page.';

$priv_list['page-openvpn-user-import']['match'] = [];
$priv_list['page-openvpn-user-import']['match'][] = 'vpn_openvpn_user_import.php*';
