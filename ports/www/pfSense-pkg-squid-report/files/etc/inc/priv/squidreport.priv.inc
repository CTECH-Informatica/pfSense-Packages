<?php
/*
 * squidreport.priv.inc
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

$priv_list['page-squid-report'] = [];
$priv_list['page-squid-report']['name'] = 'WebCfg - Squid: Report';
$priv_list['page-squid-report']['descr'] = 'Allow access to Squid Report package GUI';

$priv_list['page-squid-report']['match'] = [];
$priv_list['page-squid-report']['match'][] = 'pkg_edit.php?xml=squid-report.xml*';

