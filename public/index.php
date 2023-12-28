<?php declare(strict_types=1);

use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 80);

$server->on(
    'request',
    function (Request $request, Response $response) {
        if (!empty($request->get['sleep'])) {
            Coroutine::sleep((float) $request->get['sleep']); // Sleep for a while if HTTP query parameter "sleep" presents.
        }

        // Next method call is to show how to change HTTP status code from the default one (200) to something else.
        $response->status(200, 'Test');

        $response->end(
            <<<'EOT'
                <pre>
                In this example we start an HTTP/1 server.

                NOTE: The autoreloading feature is enabled. If you update this PHP script and
                then refresh URL http://127.0.0.1, you should see the changes made.
                </pre>

            EOT
        );
    }
);

$server->start();
