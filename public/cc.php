<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache reseteado OK';
} else {
    echo 'OPcache no activo (no era necesario)';
}
