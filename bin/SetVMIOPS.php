#!/usr/bin/php
<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
ini_set('soap.wsdl_cache_enabled', '0');
ini_set('default_socket_timeout', 1000);
ini_set('max_input_time', '0');
ini_set('max_execution_time', '0');
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);
if ($_SERVER['argc'] < 2)
	die("Call like {$_SERVER['argv'][0]} <vps>\nwhere <vps> is the ID or vmId of a vps\n");
$db = get_module_db('vps');
$db->query("select * from vps where vps_id='" . $db->real_escape($_SERVER['argv'][1]) . "' or vps_vzid='" . $db->real_escape($_SERVER['argv'][1]) . "'");
if ($db->num_rows() == 0)
	die("didn't find a VPS matching this id or vzid {$_SERVER['argv'][1]} in db");
$db->next_record(MYSQL_ASSOC);
$master = get_service_master($db->Record['vps_server'], 'vps', true);
echo "Loaded VPS {$db->Record['vps_id']} vmId {$db->Record['vps_vzid']} Server #{$master['vps_id']} {$master['vps_name']}\n";
try {
	$parameters = [
		'vmId' => $db->Record['vps_vzid'],
		'minimumOps' => 2 +  (2 * $db->Record['vps_slices']),
		'maximumOps' => 250 + (100 * $db->Record['vps_slices']),
		'adminUsername' => 'Administrator',
		'adminPassword' => $master['vps_root']
	];
	echo 'Calling SetVMIOPS with a parameters ' . print_r($parameters, true) . "\n";
	$params = [
		'encoding' => 'UTF-8',
		'verifypeer' => FALSE,
		'verifyhost' => FALSE,
		'soap_version' => SOAP_1_2,
		'trace' => 1,
		'exceptions' => 1,
		'connection_timeout' => 180,
		'stream_context' => stream_context_create([
			'ssl' => [
				'ciphers' => 'RC4-SHA',
				'verify_peer' => FALSE,
				'verify_peer_name' => FALSE
		]])
	];
	$soap = new SoapClient("https://{$master['vps_ip']}/HyperVService/HyperVService.asmx?WSDL", $params);
	$response = $soap->SetVMIOPS($parameters);
	echo 'SetVMIOPS returned ' . print_r($response->SetVMIOPSResult, true) . "\n";
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage()."\n";
}
