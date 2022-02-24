<?php

namespace rdx\querylog;

use Closure;

class EnableQueryLogMiddleware {

	public function handle($request, Closure $next, $guard = null) {
		querylog_maybe_enable();

		return $next($request);
	}

}
