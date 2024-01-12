<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Util\Vite;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class AssetController
{
    public function __construct(private readonly Vite $vite)
    {
        
    }

    public function processStaticRequest(Request $request) : Response
    {
        $entryPoint = $request->getRouteParameters()['entrypoint'];
        $filepath = $this->vite->getAbsoluteFilepath($entryPoint);
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        if($extension === 'js') {
            return Response::createJs(file_get_contents($filepath));
        } else if($extension === 'css') {
            return Response::createCss(file_get_contents($filepath));
        } else if($extension === 'png') {
            return Response::createPng(file_get_contents($filepath));
        } else if($extension === 'ico') {
            return Response::createIco(file_get_contents($filepath));
        } else if($extension === 'woff') {
            return Response::createWoff(file_get_contents($filepath));
        } else if($extension === 'woff2') {
            return Response::createWoff2(file_get_contents($filepath));
        }
        return Response::createBadRequest();
    }
}