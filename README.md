Simple query log/display for Laravel
====

Not really a log, bad name, but an inline HTML display.

Enable
----

1. Install with composer.
2. Add `'querylog_ips'` config to `config/app.php`, see examples below.  
    You can take that from `.env` of course, with any var name you want.
3. Add `'rdx\querylog\EnableQueryLogMiddleware'` middleware in `App/Http/Kernel.php`.
4. Print `querylog_html()` output in your HTML somewhere.

Output:

![querylog output](https://raw.githubusercontent.com/rudiedirkx/laravel-querylog/master/output.png)

`querylog_ips` examples
----

```
'querylog_ips' => '127.0.0.1',
'querylog_ips' => '::1',
'querylog_ips' => '192.168.0.0/16,12.23.34.45',
'querylog_ips' => '12.23.34.45,1c10:7181:24d9::/48',
```

Comma separated. **No space!** Optional CIDR range format: `addr/range`. IPv4 and IPv6 are supported.

If you want **every** IP address to show debug:

```
'querylog_ips' => '0.0.0.1/0,::1/0',
```
