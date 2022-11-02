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

		$GLOBALS['querylog_models'] = [];
		\Event::listen("eloquent.retrieved: *", function($type, $args) {
			$model = $args[0];
			$class = get_class($model);
			if (!isset($GLOBALS['querylog_models'][$class])) {
				$GLOBALS['querylog_models'][$class] = 1;
			}
			else {
				$GLOBALS['querylog_models'][$class]++;
			}
		});
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

	$models = $GLOBALS['querylog_models'] ?? [];

	return compact('all', 'doubles', 'models', 'time');
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

	$models = number_format(array_sum($log['models']), 0, '.', '_');

	$modelsHtml = '';
	arsort($log['models'], SORT_NUMERIC);
	foreach ($log['models'] as $class => $num) {
		$modelsHtml .= '<li>' . $class . ' - ' . $num . '</li>';
	}
	$modelsHtml and $modelsHtml = "Models:<ul>$modelsHtml</ul>";

	$ms = round($log['time']);

	$mb = number_format(memory_get_peak_usage() / 1e6, 1);

	$reqTime = isset($_SERVER['REQUEST_TIME_FLOAT']) ? number_format(1000 * (microtime(1) - $_SERVER['REQUEST_TIME_FLOAT']), 0) : '?';

	return "
		<details class='querylog' style='font-family: monospace'>
			<summary>$count queries, $doublesSummary doubles, $models models, in $ms ms | $mb MB | req <span id='querylog-request-time'>$reqTime</span> ms</summary>
			$modelsHtml
			$doublesHtml
			$allHtml
		</details>
	";
}
