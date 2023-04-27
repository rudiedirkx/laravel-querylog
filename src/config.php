<?php

return [
	'ips' => array_filter(explode(',', env('QUERYLOG_IPS', ''))),

	'track_models' => true,

	'track_services' => true,
];
