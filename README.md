Simple query log/display for Laravel
====

Not really a log, bad name, but an inline HTML display.

Enable
----

1. Install with composer.
2. Add `QUERYLOG_IPS` to your `.env`, see examples below.
4. Print `querylog_html()` output in your HTML somewhere.

Output:

![querylog output](https://raw.githubusercontent.com/rudiedirkx/laravel-querylog/master/output.png)

`QUERYLOG_IPS` examples
----

```
QUERYLOG_IPS=127.0.0.1
QUERYLOG_IPS=::1
QUERYLOG_IPS=192.168.0.0/16,12.23.34.45
QUERYLOG_IPS=12.23.34.45,1c10:7181:24d9::/48
```

Comma separated. **No space!** Optional CIDR range format: `addr/range`. IPv4 and IPv6 are supported.

If you want **every** IP address to show debug:

```
QUERYLOG_IPS=0.0.0.1/0,::1/0
```
