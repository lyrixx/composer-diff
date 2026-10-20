# Composer Lock Comparison

This project host a web application where you can paste two `composer.lock` and
get a beautiful diff of the two files.

## Deployment

This website is [deployed on github pages](https://lyrixx.github.io/composer-diff/).

## Installation

```
castor app:install
castor app:build
castor app:serve
open http://127.0.0.1:9999/
```

## Internal

This website use WASM to run a PHP interpreter in the browser.
PHP is built by [php-wasm-builder](https://github.com/lyrixx/php-wasm-builder).
You can have a look to `castor.php` to see how it works.

## Thanks

We would like to thanks the following projects, for inspiration, code, or both:

* https://github.com/soyuka/php-wasm
* https://github.com/IonBazan/composer-diff
