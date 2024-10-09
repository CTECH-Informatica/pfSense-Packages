<?php
/*
 * squid-report.inc
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

require_once('squid-report-util.inc');
require_once('config.inc');
require_once('globals.inc');
require_once('util.inc');
require_once('pfsense-utils.inc');
require_once('pkg-utils.inc');
require_once('service-utils.inc');

if (!file_exists('/usr/local/pkg/squid.inc')) {
    log_error('[squidreport] No squid.inc found. You must have Squid package installed to use Squid Report.');
}

define('SQUID_REPORT_WWW', '/usr/local/www/squid_report');
define('SQUID_REPORT_FILES', '/usr/local/etc/squid_report');
define('SQUID_REPORT_PHP_BIN', '/usr/local/bin/php');
define('SQUID_REPORT_SERVICE_BIN', '/usr/local/sbin/squid_report_nginx');
define('SQUID_REPORT_NGINX_BIN', '/usr/local/sbin/nginx');
define('SQUID_REPORT_NGINX_CONF', '/usr/local/etc/nginx/squid_report.conf');
define('SQUID_REPORT_RC_FILENAME', 'squid_report_nginx.sh');
define('SQUID_REPORT_RC_FILE', '/usr/local/etc/rc.d/' . SQUID_REPORT_RC_FILENAME);
define('SQUID_REPORT_RC_PID', '/var/run/nginx_squid_report.pid');
define('SQUID_REPORT_NGINX_HTPASSWD', '/usr/local/etc/nginx/.squid_report_htpasswd');
define('SQUID_REPORT_CRON_PARSER', SQUID_REPORT_PHP_BIN . ' ' . SQUID_REPORT_FILES . '/parser.php');
define('SQUID_REPORT_CRON_DB_OPTIMIZER', SQUID_REPORT_PHP_BIN . ' ' . SQUID_REPORT_FILES . '/optimize_db.php');
define('SQUID_REPORT_CRON_REMOVE_OLD', SQUID_REPORT_PHP_BIN . ' ' . SQUID_REPORT_FILES . '/remove_old.php');
define('SQUID_REPORT_LOGS', '/var/log/squid_report');
define('SQUID_REPORT_LOG_FILE', SQUID_REPORT_LOGS . '/squid_report.log');

function squid_report_install()
{
    safe_mkdir(SQUID_REPORT_LOGS);

    if (!file_exists(SQUID_REPORT_LOG_FILE)) {
        file_put_contents(SQUID_REPORT_LOG_FILE, '');
        chmod(SQUID_REPORT_LOG_FILE, 0655);
    }

    try {
        require_once '/usr/local/etc/squid_report/squid_report.inc.php';

        $db = connect_db();

        check_schema($db);
    } catch (Throwable $ex) {
        log_error("[squidreport] {$ex->getMessage()}");
        log_error("[squidreport] {$ex->getTraceAsString()}");
    }
}

function squid_report_deinstall()
{
    squid_report_disable();

    unlink_if_exists(SQUID_REPORT_SERVICE_BIN);
    unlink_if_exists(SQUID_REPORT_NGINX_CONF);
    unlink_if_exists(SQUID_REPORT_NGINX_HTPASSWD);
    unlink_if_exists(SQUID_REPORT_RC_FILE);
//    unlink_if_exists(SQUID_REPORT_LOG_FILE);

    $squidReportWww = SQUID_REPORT_WWW;
    log_error("[squidreport] Will NOT delete Squid Report web dir '{$squidReportWww}'. Delete manually if required.");
//    if (is_dir(SQUID_REPORT_WWW)) {
//        rmdir_recursive(SQUID_REPORT_WWW);
//    }

    $squidReportFiles = SQUID_REPORT_FILES;
    log_error("[squidreport] Will NOT delete Squid Report files dir '{$squidReportFiles}' to keep history. Delete manually if required.");
//    if (is_dir(SQUID_REPORT_FILES)) {
//        rmdir_recursive(SQUID_REPORT_FILES);
//    }

    $squidReportLogs = SQUID_REPORT_LOGS;
    log_error("[squidreport] Will NOT delete Squid Report logs dir '{$squidReportLogs}' to keep history. Delete manually if required.");
//    if (is_dir(SQUID_REPORT_LOG)) {
//        rmdir_recursive(SQUID_REPORT_LOG);
//    }

    log_error('[squidreport] Will NOT delete Squid Report config to keep history. Delete manually if required.');
//    config_del_path('installedpackages/squidreport');
//
//    write_config('Squid report deinstall');
}

function squid_report_after_form() {
    $access_report_href = '#';
    $access_report_target = '_self';

    $pkg_config = config_get_path('installedpackages/squidreport/config/0', []);

    $server_host = explode(':', $_SERVER['HTTP_HOST'])[0];

    if (!array_key_exists('squid_report_port', $pkg_config) || empty($pkg_config['squid_report_port'])) {
        $pkg_config['squid_report_port'] = '7777';
    }

    if (array_key_exists('squid_report_enable', $pkg_config) && $pkg_config['squid_report_enable'] === 'on') {
        $access_report_href = "http://{$server_host}:{$pkg_config['squid_report_port']}/";
        $access_report_target = '_blank';
    }

    $js = <<<JS
<script>
    events.push(function() {
        $('#navto_squid_report').attr('href', '{$access_report_href}');
        $('#navto_squid_report').attr('target', '{$access_report_target}');
    });
</script>
JS;

    echo $js;
}

function squid_report_validate_input(array $post, array &$input_errors)
{
    global $config;

    $squid_settings = config_get_path('installedpackages/squid/config/0', []);

    if (empty($squid_settings)) {
        $input_errors[] = "Please, configure Squid package 'General' settings first.";
    }

    if ($squid_settings['log_enabled'] !== 'on') {
        $input_errors[] = "Please, enable Access Logging in Squid package 'General' settings first.";
    }

    if ($squid_settings['log_dir'] === '') {
        $input_errors[] = "Please, configure Log Store Directory in Squid package 'General' settings first.";
    }

    $port = (int)$post['squid_report_port'];

    if ($post['squid_report_port'] === '' || !is_numeric($post['squid_report_port']) || $port < 1024 || $port > 65535) {
        $input_errors[] = "{$post['squid_report_port']} is not valid port number.";
    }

    if ($post['squid_report_port'] === 80 && !isset($config['system']['webgui']['disablehttpredirect'])) {
        $input_errors[] = 'Port 80 cannot be used until Webgui redirect is disabled under system -> advanced';
    }

    if ($post['squid_report_port'] === 80 && $config['system']['webgui']['protocol'] === 'http') {
        if ($config['system']['webgui']['port'] === '' || $config['system']['webgui']['port'] === '80') {
            $input_errors[] = 'Port 80 cannot be used while pfSense is in HTTP mode and using the same port 80.';
        }
    }

    $logsRetain = (int)$post['squid_report_logs_retain'];

    if ($post['squid_report_logs_retain'] === '' || !is_numeric($post['squid_report_logs_retain']) || $logsRetain < 1 || $logsRetain > 180) {
        $input_errors[] = "{$post['squid_report_logs_retain']} is not valid days number.";
    }
}

function squid_report_resync()
{
    global $config;

    $pkg_config = config_get_path('installedpackages/squidreport/config/0', []);

    if (empty($pkg_config)) {
        return;
    }

    if ($pkg_config['squid_report_enable'] !== 'on') {
        squid_report_disable();
        return;
    }

    squid_report_check();

    squid_report_setup_cron();

    system(SQUID_REPORT_RC_FILE . ' start');
}

function  squid_report_check_nginx()
{
    if (!file_exists(SQUID_REPORT_NGINX_BIN)) {
        log_error('[squidreport] Nginx not found...');
    }

    if (!file_exists(SQUID_REPORT_SERVICE_BIN)) {
        copy(SQUID_REPORT_NGINX_BIN, SQUID_REPORT_SERVICE_BIN);
        chmod(SQUID_REPORT_SERVICE_BIN, 0755);
    }

    if (md5_file(SQUID_REPORT_NGINX_BIN, true) !== md5_file(SQUID_REPORT_SERVICE_BIN, true)) {
        unlink(SQUID_REPORT_SERVICE_BIN);
        copy(SQUID_REPORT_NGINX_BIN, SQUID_REPORT_SERVICE_BIN);
        chmod(SQUID_REPORT_SERVICE_BIN, 0755);
    }

    if (substr(sprintf('%o', fileperms(SQUID_REPORT_SERVICE_BIN)), -4) !== '0755') {
        chmod(SQUID_REPORT_SERVICE_BIN, 0755);
    }
}

function squid_report_check()
{
    $pkg_config = config_get_path('installedpackages/squidreport/config/0', []);

    if (empty($pkg_config)) {
        return;
    }

    squid_report_check_nginx();

    $pid_file = SQUID_REPORT_RC_PID;
    $nginx_htpasswd = SQUID_REPORT_NGINX_HTPASSWD;
    $log_file = SQUID_REPORT_LOG_FILE;

    $conf_template=<<<EOF
    # nginx configuration file
    
	user root wheel;
	worker_processes 1;
	
	pid	{$pid_file};
	error_log syslog:server=unix:/var/run/log,facility=local5;
	error_log {$log_file} warn;
	
	events {
	    worker_connections  1024;
	}
	
	http {
	    include /usr/local/etc/nginx/mime.types;
        default_type application/octet-stream;
        
        sendfile on;
		access_log syslog:server=unix:/var/run/log,facility=local5 combined;
		keepalive_timeout 65;
		
		server {
		    listen {$pkg_config['squid_report_port']};
		    listen [::]:{$pkg_config['squid_report_port']};

	        gzip on;
            gzip_comp_level 5;
            gzip_min_length 256;
            gzip_proxied any;
            gzip_vary on;
            
            server_name squid-report.pfsense
            server name 127.0.0.1
            client_max_body_size 5M;

            root "/usr/local/www/squid_report/";
            
            set_real_ip_from 0.0.0.0/0;
            index index.php;
            try_files \$uri \$uri/ /index.php?\$args;
	
			location / {
			    try_files \$uri \$uri/ /index.php?\$args;
            }

            location ~ \.php$ {
                include /usr/local/etc/nginx/fastcgi_params;
                fastcgi_read_timeout 120s;
                fastcgi_buffers 16 16k;
                fastcgi_buffer_size 32k;
                
                fastcgi_pass unix:/var/run/php-fpm.socket;
                fastcgi_index index.php;
                fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            }
            
            auth_basic "Squid report auth";
            auth_basic_user_file {$nginx_htpasswd};
        }
	}
EOF;

    file_put_contents(SQUID_REPORT_NGINX_CONF, $conf_template, LOCK_EX);

    $auth_user = $pkg_config['squid_report_user'];
    $auth_pass = crypt($pkg_config['squid_report_password'], base64_encode($pkg_config['squid_report_password']));

    file_put_contents($nginx_htpasswd, "{$auth_user}:{$auth_pass}", LOCK_EX);

    $service_bin = SQUID_REPORT_SERVICE_BIN;
    $nginx_conf = SQUID_REPORT_NGINX_CONF;

    $rc = [];
    $rc['file'] = SQUID_REPORT_RC_FILENAME;
    $rc['start'] = <<<EOF
	if [ -f {$pid_file} ]; then
	    cat {$pid_file} | xargs ps ||
	    {$service_bin} -c {$nginx_conf}
    else 
	    {$service_bin} -c {$nginx_conf}
	fi
	
	chmod go+rw /var/run/php-fpm.socket
EOF;

    $rc['stop'] = <<<EOF
	if [ -f {$pid_file} ]; then
	    kill `cat {$pid_file}`
	fi
EOF;

    if (file_exists(SQUID_REPORT_RC_FILE)) {
        system(SQUID_REPORT_RC_FILE . ' stop');
    }

    write_rcfile($rc);
}

function squid_report_setup_cron()
{
    $pkg_config = config_get_path('installedpackages/squidreport/config/0', []);

    if (empty($pkg_config)) {
        return;
    }

    if ($pkg_config['squid_report_enable'] !== 'on') {
        squid_report_remove_cron();
        return;
    }

    $minute = $hour = '*';

    switch ($pkg_config['squid_report_refreshsheduler_time']) {
        case 'lhp_10m':
            $minute = '*/10';
            break;
        case 'lhp_20m':
            $minute = '*/20';
            break;
        case 'lhp_30m':
            $minute = '*/30';
            break;
        case 'lhp_40m':
            $minute = '*/40';
            break;
        case 'lhp_50m':
            $minute = '*/50';
            break;
        case 'lhp_60m':
            $minute = '*/60';
            break;
        case 'lhp_2h':
            $minute = '0';
            $hour = '*/2';
            break;
        case 'lhp_3h':
            $minute = '0';
            $hour = '*/3';
            break;
        case 'lhp_4h':
            $minute = '0';
            $hour = '*/4';
            break;
        case 'lhp_6h':
            $minute = '0';
            $hour = '*/6';
            break;
        case 'lhp_8h':
            $minute = '0';
            $hour = '*/8';
            break;
        case 'lhp_12h':
            $minute = '0';
            $hour = '*/12';
            break;
        case 'lhp_24h':
            $minute = '45';
            $hour = '23';
            break;
    }

    install_cron_job(SQUID_REPORT_CRON_PARSER . ' --today', true, $minute, $hour, '*', '*', '*', 'root');
    install_cron_job(SQUID_REPORT_CRON_PARSER . ' --yesterday', true, '15', '0', '*', '*', '*', 'root');

    install_cron_job(SQUID_REPORT_CRON_REMOVE_OLD , true, '1', '0', '*', '*', '7', 'root');
    install_cron_job(SQUID_REPORT_CRON_DB_OPTIMIZER , true, '2', '0', '*', '*', '7', 'root');
}

function squid_report_remove_cron()
{
    install_cron_job(SQUID_REPORT_CRON_PARSER . ' --today', false);
    install_cron_job(SQUID_REPORT_CRON_PARSER . ' --yesterday', false);
    install_cron_job(SQUID_REPORT_CRON_REMOVE_OLD, false);
    install_cron_job(SQUID_REPORT_CRON_DB_OPTIMIZER, false);
}

function squid_report_disable()
{
    // Remove cronjobs

    squid_report_remove_cron();

    // Remove nginx service

    exec(SQUID_REPORT_RC_FILE . ' stop');
    unlink_if_exists(SQUID_REPORT_RC_FILE);
}
