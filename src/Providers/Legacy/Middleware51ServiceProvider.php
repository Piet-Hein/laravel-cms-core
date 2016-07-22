<?php
namespace Czim\CmsCore\Providers\Legacy;

use Czim\CmsCore\Providers\MiddlewareServiceProvider;

class Middleware51ServiceProvider extends MiddlewareServiceProvider
{
    /**
     * Grouped middleware does not exist in Laravel 5.1
     *
     * {@inheritdoc}
     */
    protected function registerCmsGroupMiddleware()
    {
        foreach ($this->getGlobalMiddleware() as $middleware) {

            // Don't add if the middleware is already globally enabled in the kernel
            if ($this->kernel->hasMiddleware($middleware)) {
                continue;
            }

            $this->kernel->pushMiddleware($middleware);
        }

        return $this;
    }

}
