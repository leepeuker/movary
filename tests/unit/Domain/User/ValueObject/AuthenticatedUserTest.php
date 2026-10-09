<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\ValueObject;

use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticatedUser::class)]
#[CoversClass(CredentialType::class)]
class AuthenticatedUserTest extends TestCase
{
    #[DataProvider('credentialTypeProvider')]
    public function testCreate(CredentialType $credentialType) : void
    {
        $subject = AuthenticatedUser::create(12, $credentialType);

        self::assertSame(12, $subject->getUserId());
        self::assertSame($credentialType, $subject->getCredentialType());
    }

    /** @return array<string, array{CredentialType}> */
    public static function credentialTypeProvider() : array
    {
        return [
            'API token' => [CredentialType::API_TOKEN],
            'web session' => [CredentialType::WEB_SESSION],
        ];
    }
}
