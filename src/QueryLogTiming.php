<?php

namespace rdx\querylog;

class QueryLogTiming {

	protected float $start;

	public function __construct(
		protected string $message,
	) {
		$this->start = microtime(true);
	}

	public function track(string $message = '') : void {
		if ($message != '') {
			$message = " | $message";
		}
		querylog_track(sprintf('[%.2f s] %s %s', microtime(true) - $this->start, $this->message, $message));
	}

}
