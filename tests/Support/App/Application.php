<?php

namespace Huynt57\LaravelSingletonize\Tests\Support\App;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;

class Application extends Container
{
    public function __construct(array $config = [])
    {
        parent::__construct();

        $this->instance('config', new Repository($config));
    }

    public function register($provider): void
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();
        $provider->boot();
    }
}
