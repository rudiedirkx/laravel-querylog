<?php

function querylog_semidebug() {
	$ips = config('app.querylog_ips');
	if (!trim($ips)) return false;
	if (empty($_SERVER['REMOTE_ADDR'])) return false;

	$ip = $_SERVER['REMOTE_ADDR'];
	$regex = '#^' . strtr($ips, ['*' => '\d+', ',' => '|', '.' => '\\.']) . '$#';
	try {
		return preg_match($regex, $ip);
	}
	catch (\Exception $ex) {}
	return false;
}

function querylog_maybe_enable() {
	if (querylog_semidebug()) {
		\DB::enableQueryLog();
	}
}

function querylog_replace($sql, $params) {
	return preg_replace_callback('#\?#', function() use (&$params) {
		return "'" . array_shift($params) . "'";
	}, $sql);
}

function querylog_get() {
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

	return compact('all', 'doubles', 'time');
}

function querylog_html() {
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

	$ms = round($log['time']);

	$mb = number_format(memory_get_peak_usage() / 1e6, 1);

	return "
		<details class=\"querylog\" style='font-family: monospace'>
			<summary>$count queries, $doublesSummary doubles, in $ms ms ($mb MB)</summary>
			$doublesHtml
			$allHtml
		</details>
	";
}
