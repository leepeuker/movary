<?php declare(strict_types=1);

namespace Movary\Service\FlashMessage;

use Closure;
use Movary\Service\CookieSecurity;
use Movary\Util\Cookie;

class FlashMessageService
{
    public const string COOKIE_NAME_PREFIX = 'movary_flash_';

    private const int LIFETIME_IN_SECONDS = 300;

    public function __construct(
        private readonly FlashMessageCookieCodec $codec,
        private readonly Cookie $cookie,
        private readonly CookieSecurity $cookieSecurity,
        private readonly Closure $clock,
    ) {
    }

    public function add(FlashMessage $message) : void
    {
        $expires = ($this->clock)() + self::LIFETIME_IN_SECONDS;
        $this->cookie->set(
            $this->createCookieName($message),
            $this->codec->encode($message, $expires),
            $expires,
            $this->cookieSecurity->isSecure(),
        );
    }

    public function clear() : void
    {
        foreach (FlashMessage::cases() as $message) {
            $cookieName = $this->createCookieName($message);
            if ($this->cookie->find($cookieName) !== null) {
                $this->cookie->delete($cookieName, $this->cookieSecurity->isSecure());
            }
        }
    }

    /** @return array<FlashMessage> */
    public function consumeFor(FlashMessageDestination $destination) : array
    {
        $consumed = [];

        foreach (FlashMessage::cases() as $message) {
            if ($message->getDestination() !== $destination) {
                continue;
            }

            $cookieName = $this->createCookieName($message);
            $cookieValue = $this->cookie->find($cookieName);
            if ($cookieValue === null) {
                continue;
            }

            $this->cookie->delete($cookieName, $this->cookieSecurity->isSecure());
            if ($this->codec->isValid($message, $cookieValue, ($this->clock)()) === true) {
                $consumed[] = $message;
            }
        }

        return $consumed;
    }

    private function createCookieName(FlashMessage $message) : string
    {
        return self::COOKIE_NAME_PREFIX . $message->value;
    }
}
