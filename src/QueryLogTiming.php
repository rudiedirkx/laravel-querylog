<?php

namespace rdx\querylog;

class QueryLogTiming {

	protected int $start;

	public function __construct(
		protected string $message,
	) {
		$this->start = hrtime(true);
	}

	public function get() : float {
		return (hrtime(true) - $this->start) / 1e6;
	}

	public function dump(string $message = '') : void {
		if ($message != '') {
			$message = " | $message";
		}

		dump($this->message . $message, $this->get());
	}

	public function track(string $message = '') : void {
		$ms = $this->get();

		if ($message != '') {
			$message = " | $message";
		}

		if ($ms > 100) {
			$time = $ms / 1000;
			$timeUnit = 's';
		}
		else {
			$time = $ms;
			$timeUnit = 'ms';
		}

		querylog_track(sprintf('[%.2f %s] %s %s', $time, $timeUnit, $this->message, $message));
	}

}
