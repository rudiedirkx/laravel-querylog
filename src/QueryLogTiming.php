<?php

namespace rdx\querylog;

class QueryLogTiming {

	protected float $start;

	public function __construct(
		protected string $message,
	) {
		$this->start = microtime(true);
	}

	public function dump(string $message = '') : void {
		$sec = microtime(true) - $this->start;

		if ($message != '') {
			$message = " | $message";
		}

		dump($this->message . $message, 1000 * $sec);
	}

	public function track(string $message = '') : void {
		$sec = microtime(true) - $this->start;

		if ($message != '') {
			$message = " | $message";
		}

		if ($sec > 0.1) {
			$time = $sec;
			$timeUnit = 's';
		}
		else {
			$time = $sec * 1000;
			$timeUnit = 'ms';
		}

		querylog_track(sprintf('[%.2f %s] %s %s', $time, $timeUnit, $this->message, $message));
	}

}
