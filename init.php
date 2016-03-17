<?php

namespace Bolt\Extension\Cainc\ContentRevert;

if (isset($app)) {
    $app['extensions']->register(new Extension($app));
}
