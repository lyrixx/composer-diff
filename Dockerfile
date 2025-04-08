FROM emscripten/emsdk:3.1.35 AS build_tool

RUN apt-get update && \
  apt-get --no-install-recommends -y install \
    build-essential \
    automake \
    autoconf \
    libtool \
    pkgconf \
    bison \
    flex \
    re2c \
    gdb \
    libxml2-dev \
    pv \
    re2c

FROM build_tool AS php_src
ARG PHP_BRANCH=PHP-8.3.0
RUN git clone https://github.com/php/php-src.git php-src \
        --branch $PHP_BRANCH \
        --single-branch \
        --depth 1

FROM php_src AS php-wasm
ARG WASM_ENVIRONMENT=web
ARG JAVASCRIPT_EXTENSION=mjs
ARG EXPORT_NAME=createPhpModule
ARG MODULARIZE=1
ARG EXPORT_ES6=1
ARG ASSERTIONS=0
ARG OPTIMIZE=-O1
# TODO: find a way to keep this, it can't be empty if defined...
# ARG PRE_JS=
ARG INITIAL_MEMORY=256mb

RUN cd /src/php-src && ./buildconf --force \
    && emconfigure ./configure \
        --enable-embed=static \
        --with-layout=GNU  \
        --disable-cgi      \
        --disable-cli      \
        --disable-fiber-asm \
        --disable-all      \
        --enable-session   \
        --enable-filter    \
        --disable-rpath    \
        --disable-phpdbg   \
        --without-pear     \
        --with-valgrind=no \
        --without-pcre-jit \
        --enable-json      \
        --enable-ctype     \
        --enable-mbstring  \
        --disable-mbregex  \
        --with-config-file-scan-dir=/src/php  \
        --enable-tokenizer
RUN cd /src/php-src && emmake make -j8
# PHP7 outputs a libphp7 whereas php8 a libphp
RUN cd /src/php-src && bash -c '[[ -f .libs/libphp7.la ]] && mv .libs/libphp7.la .libs/libphp.la && mv .libs/libphp7.a .libs/libphp.a && mv .libs/libphp7.lai .libs/libphp.lai || exit 0'
COPY ./source /src/source
RUN cd /src/php-src && emcc $OPTIMIZE \
        -I .     \
        -I Zend  \
        -I main  \
        -I TSRM/ \
        -c \
        /src/source/phpw.c \
        -o /src/phpw.o \
        -s ERROR_ON_UNDEFINED_SYMBOLS=0
RUN mkdir /build && cd /src/php-src && emcc $OPTIMIZE \
    -o /build/php-$WASM_ENVIRONMENT.$JAVASCRIPT_EXTENSION \
    --llvm-lto 2                     \
    -s EXPORTED_FUNCTIONS='["_phpw_with_args"]' \
    -s EXTRA_EXPORTED_RUNTIME_METHODS='["ccall", "UTF8ToString", "lengthBytesUTF8", "FS"]' \
    -s ENVIRONMENT=$WASM_ENVIRONMENT    \
    -s FORCE_FILESYSTEM=1            \
    -s MAXIMUM_MEMORY=2gb             \
    -s INITIAL_MEMORY=$INITIAL_MEMORY \
    -s ALLOW_MEMORY_GROWTH=1         \
    -s ASSERTIONS=$ASSERTIONS      \
    -s ERROR_ON_UNDEFINED_SYMBOLS=0  \
    -s MODULARIZE=$MODULARIZE        \
    -s INVOKE_RUN=0                  \
    -s LZ4=1                  \
    -s EXPORT_ES6=$EXPORT_ES6 \
    -s EXPORT_NAME=$EXPORT_NAME \
    # -s DECLARE_ASM_MODULE_EXPORTS=0 \
    -lidbfs.js                       \
        /src/phpw.o .libs/libphp.a
RUN rm -r /src/*

FROM scratch
COPY --from=php-wasm /build/ .
