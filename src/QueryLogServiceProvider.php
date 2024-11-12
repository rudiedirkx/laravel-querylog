<?php

namespace rdx\querylog;

use Illuminate\Support\ServiceProvider;
use Throwable;

class QueryLogServiceProvider extends ServiceProvider {

	public function boot() : void {
		$this->bootService();

		$this->bootLogs();
	}

	protected function bootService() : void {
		$file = __DIR__ . '/config.php';
        $this->mergeConfigFrom($file, 'querylog');

		if ($this->app->runningInConsole()) {
	        $this->publishes([
	        	$file => config_path('querylog.php'),
	        ]);
	    }
	}

	protected function bootLogs() : void {
		if (!$this->app->runningInConsole() && \querylog_semidebug()) {
			$config = $this->app['config']->get('querylog', []);

			$this->bootLogQueries();

			$GLOBALS['querylog_models'] = [];
			if ($config['track_models']) {
				$this->bootLogModels();
			}

			$GLOBALS['querylog_services'] = [];
			if ($config['track_services']) {
				$this->bootLogServices();
			}

			$GLOBALS['querylog_tracked'] = [];
		}
	}

	protected function bootLogQueries() : void {
		try {
			$this->app['db']->enableQueryLog();
		}
		catch (Throwable $ex) {}
	}

	protected function bootLogModels() : void {
		$this->app['events']->listen("eloquent.retrieved: *", function($type, $args) {
			$model = $args[0];
			$class = get_class($model);
			$GLOBALS['querylog_models'][$class] ??= 0;
			$GLOBALS['querylog_models'][$class]++;
		});
	}

	protected function bootLogServices() : void {
		$this->app->afterResolving(function($resolved) {
			if (is_object($resolved)) {
				$resolved = get_class($resolved);
			}

			if (is_string($resolved)) {
				$GLOBALS['querylog_services'][$resolved] ??= 0;
				$GLOBALS['querylog_services'][$resolved]++;
			}
		});
	}

}
