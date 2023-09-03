<?php

use FastRoute\RouteCollector;

class MiddlewareService extends RouteCollector
{
    public function addMiddleware()
    {
        $this->getData();
    }
}