# Composer Lock Comparison

This project host a web application where you can paste two `composer.lock` and
get a beautiful diff of the two files.

## Deployment

This website is [deployed on github pages](https://lyrixx.github.io/composer-diff/).

## Installation

```
castor install
castor wasm:export --pack --build
castor serve
open http://127.0.0.1:9999/
```

## Internal

This website use WASM to run a PHP interpreter in the browser.
You can have a look to the `Dockerfile`, and `castor.php` to see how it works.

## Thanks

We would like to thanks the following projects, for inspiration, code, or both:

* https://github.com/soyuka/php-wasm
* https://github.com/IonBazan/composer-diff
