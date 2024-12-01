<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class LandingPageController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SessionWrapper $sessionWrapper,
        private readonly bool $registrationEnabled,
        private readonly ?string $defaultEmail,
        private readonly ?string $defaultPassword,
    ) {
    }

    public function render() : Response
    {
        $deletedAccount = $this->sessionWrapper->has('deletedAccount');

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/login.html.twig', [
                'deletedAccount' => $deletedAccount,
                'registrationEnabled' => $this->registrationEnabled,
                'defaultEmail' => $this->defaultEmail,
                'defaultPassword' => $this->defaultPassword
            ]),
        );
    }
}
