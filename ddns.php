#!/usr/bin/env php
<?php
require __DIR__ . '/Cloudflare.php';

$confFile = __DIR__ . '/config.ini';

if(!file_exists($confFile))
{
	echo "Missing config file. Please copy config.php.skel to config.php and fill out the values therein.\n";
	return 1;
}

$config = parse_ini_file($confFile);
foreach (array('cloudflare_email', 'cloudflare_api_key', 'domain', 'record_name', 'ttl', 'protocol') as $key)
{
	if(!isset($config[$key]))
	{
		echo "config.php is missing the '$key' config value\n";
		return 1;
	}
}

$api = new Cloudflare($config['cloudflare_email'], $config['cloudflare_api_key']);
$domain     = $config['domain'];
$recordNames = explode(",", $config['record_name']);
if(isset($config['auth_token']) && $config['auth_token'])
{
	// API mode. Use IP from request params.
	if (empty($_GET['auth_token']) || empty($_GET['ip']) || $_GET['auth_token'] != $config['auth_token'])
	{
		echo "Missing or invalid 'auth_token' param, or missing 'ip' param\n";
		return 1;
	}
	$ip = $_GET['ip'];
}
else
{
	// Local mode. Get IP from service.
	$ip = getIP($config['protocol']);
}

$verbose = !isset($argv[1]) || $argv[1] != '-s';

try
{
	$zone = $api->getZone($domain);
	if(!$zone)
	{
		echo "Domain $domain not found\n";
		return 1;
	}
	foreach($recordNames as $recordName)
	{
		$recordName = trim($recordName);
		if($recordName != '')
		{
			$records = $api->getZoneDnsRecords($zone['id'], array('name' => $recordName));
			$record  = $records && $records[0]['name'] == $recordName ? $records[0] : null;
			if(!$record)
			{
				if($verbose)
				{
					echo "No existing record found. Creating a new one\n";
				}
				$ret = $api->createDnsRecord($zone['id'], 'A', $recordName, $ip, array('ttl' => $config['ttl']));
			}
			else if($record['type'] != 'A' || $record['content'] != $ip || $record['ttl'] != $config['ttl'])
			{
				if ($verbose)
				{
					echo "Updating record.\n";
				}
				$ret = $api->updateDnsRecord($zone['id'], $record['id'], array(
					'type'    => 'A',
					'name'    => $recordName,
					'content' => $ip,
					'ttl'     => $config['ttl'],
				));
			}
			else
			{
				if($verbose)
				{
					echo "Record appears OK. No need to update.\n";
				}
			}
		}
	}
	return 0;
}
catch(Exception $e)
{
	echo "Error: ".$e->getMessage()."\n";
	return 1;
}


function getIP($protocol)
{
	$prefixes = array('ipv4' => 'ipv4.', 'ipv6' => 'ipv6.', 'auto' => '');
	if(!isset($prefixes[$protocol]))
	{
		throw new Exception('Invalid "protocol" config value.');
	}
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => 'http://'.$prefixes[$protocol].'icanhazip.com/',
		CURLOPT_USERAGENT => 'Planetbiru DDNS'
	));
	$resp = curl_exec($curl);
	curl_close($curl);
	return trim($resp);
}
?>