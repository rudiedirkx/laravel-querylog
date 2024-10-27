<?php

use Wikimedia\IPSet;

function querylog_semidebug() : bool {
	static $debug = null;
	if (isset($debug)) return $debug;

	$ips = config('querylog.ips', []);
	if (!is_array($ips) || !count($ips)) return $debug = false;

	if (empty($_SERVER['REMOTE_ADDR'])) return $debug = false;

	$set = new IPSet($ips);

	$ip = $_SERVER['REMOTE_ADDR'];
	return $debug = $set->match($ip);
}

function querylog_replace(string $sql, array $params) : string {
	return preg_replace_callback('#\?#', function() use (&$params) {
		$value = array_shift($params);
		return $value === null ? 'NULL' : "'$value'";
	}, $sql);
}

/**
 * @return array{
	all: list<string>,
	doubles: array<string, int>,
	models: array<string, int>,
	services: array<string, int>,
	time: float
 }
 */
function querylog_get() : array {
	$queries = \DB::getQueryLog();

	$time = 0.0;
	$all = $uniques = [];
	foreach ($queries as $query) {
		$time += $query['time'];

		$sql = preg_replace('#\s+#', ' ', trim($query['query']));
		isset($uniques[$sql]) or $uniques[$sql] = 0;
		$uniques[$sql]++;

		$sql = querylog_replace($sql, $query['bindings']);
		$ms = number_format($query['time'], 1);
		$all[] = "[$ms ms] $sql";
	}

	$doubles = array_filter($uniques, function($num) {
		return $num > 1;
	});

	$models = $GLOBALS['querylog_models'] ?? [];

	$services = $GLOBALS['querylog_services'] ?? [];

	return compact('all', 'doubles', 'models', 'services', 'time');
}

function querylog_html() : string {
	if (!querylog_semidebug()) {
		return '';
	}

	$log = querylog_get();
	$count = count($log['all']);

	$allHtml = '';
	foreach ($log['all'] as $query) {
		$allHtml .= "<li>$query</li>";
	}
	$allHtml and $allHtml = "All:<ul>$allHtml</ul>";

	$doublesUnique = count($log['doubles']);
	$doublesTotal = array_sum($log['doubles']);
	$doublesSummary = $doublesTotal ? "$doublesUnique / $doublesTotal" : '0';

	$doublesHtml = '';
	foreach ($log['doubles'] as $sql => $num) {
		$doublesHtml .= '<li>' . sprintf('[% 2d x] %s', $num, $sql) . '</li>';
	}
	$doublesHtml and $doublesHtml = "Doubles:<ul>$doublesHtml</ul>";

	$models = number_format(array_sum($log['models']), 0, '.', '_');

	$modelsHtml = '';
	arsort($log['models'], SORT_NUMERIC);
	foreach ($log['models'] as $class => $num) {
		$modelsHtml .= '<li>' . $class . ' - ' . $num . '</li>';
	}
	$modelsHtml and $modelsHtml = "Models:<ul>$modelsHtml</ul>";

	$services = number_format(array_sum($log['services']), 0, '.', '_');

	$servicesHtml = '';
	arsort($log['services'], SORT_NUMERIC);
	foreach (array_slice($log['services'], 0, 5) as $class => $num) {
		$servicesHtml .= '<li>' . $class . ' - ' . $num . '</li>';
	}
	$servicesHtml and $servicesHtml = "Top 5 services:<ul>$servicesHtml</ul>";

	$ms = round($log['time']);

	$mb = number_format(memory_get_peak_usage() / 1e6, 1);

	$reqTime = isset($_SERVER['REQUEST_TIME_FLOAT']) ? number_format(1000 * (microtime(1) - $_SERVER['REQUEST_TIME_FLOAT']), 0) : '?';

	return "
		<details class='querylog' style='font-family: monospace'>
			<summary>$count queries, $doublesSummary doubles, in $ms ms | $models models | $services services | $mb MB | req <span id='querylog-request-time'>$reqTime</span> ms</summary>
			$modelsHtml
			$servicesHtml
			$doublesHtml
			$allHtml
		</details>
	";
}
