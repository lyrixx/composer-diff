#include "sapi/embed/php_embed.h"
#include <emscripten.h>
#include <stdlib.h>

#include "zend_globals_macros.h"
#include "zend_exceptions.h"
#include "zend_closures.h"

int main() {
  return 0;
}

void phpw_flush()
{
  fprintf(stdout, "\n");
  fprintf(stderr, "\n");
}

int EMBED_SHUTDOWN = 1;

void EMSCRIPTEN_KEEPALIVE phpw_with_args(char *file, char *arg1, char *arg2)
{
  setenv("USE_ZEND_ALLOC", "0", 1);
  if (EMBED_SHUTDOWN == 0) {
    php_embed_shutdown();
  }

  php_embed_init(0, NULL);
  EMBED_SHUTDOWN = 0;

  zval args_array;
  array_init(&args_array);
  add_index_string(&args_array, 0, file);
  add_index_string(&args_array, 1, arg1);
  add_index_string(&args_array, 2, arg2);
  zend_hash_str_update(&EG(symbol_table), "argv", strlen("argv"), &args_array);

  zval argc_zval;
  ZVAL_LONG(&argc_zval, 3);
  zend_hash_str_update(&EG(symbol_table), "argc", strlen("argc"), &argc_zval);

  zend_first_try {
    zend_file_handle file_handle;
    zend_stream_init_filename(&file_handle, file);

    if (php_execute_script(&file_handle) == FAILURE) {
      php_printf("Failed to execute PHP script.\n");
    }

    zend_destroy_file_handle(&file_handle);
  } zend_catch {
    /* int exit_status = EG(exit_status); */
  } zend_end_try();

  phpw_flush();
  php_embed_shutdown();
  EMBED_SHUTDOWN = 1;
}
