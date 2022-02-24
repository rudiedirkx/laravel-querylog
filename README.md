Simple query log/display for Laravel
====

Not really a log, bad name, but an inline HTML display.

Enable
----

1. Install with composer
2. Add `'querylog_ips'` config to `config/app.php`, see examples below
3. Add `'rdx\querylog\EnableQueryLogMiddleware'` middleware in `App/Http/Kernel.php`
4. Print `querylog_html()` output in your HTML somewhere

Output:

![querylog output](https://raw.githubusercontent.com/rudiedirkx/laravel-querylog/master/output.png)

`querylog_ips` examples
----

- `127.0.0.1`
- `127.0.*.*,192.168.*.*,12.23.34.45`

Comma separated. `*` is any number. `127.0.*` won't match, because it only has 3 numbers.
