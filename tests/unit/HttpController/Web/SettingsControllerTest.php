<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\SettingsController;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Twig\Environment;

#[CoversClass(SettingsController::class)]
#[AllowMockObjectsWithoutExpectations]
class SettingsControllerTest extends TestCase
{
    private Authentication|MockObject $authenticationMock;

    private FlashMessageService|MockObject $flashMessageServiceMock;

    private SettingsController $subject;

    private Environment|MockObject $twigMock;

    private UserApi|MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->subject = $this->createSubject([
            'twig' => $this->twigMock,
            'authenticationService' => $this->authenticationMock,
            'userApi' => $this->userApiMock,
            'flashMessageService' => $this->flashMessageServiceMock,
        ]);

        $user = $this->createMock(UserEntity::class);
        $user->method('hasCoreAccountChangesDisabled')->willReturn(false);
        $this->authenticationMock->method('getCurrentUserId')->willReturn(42);
        $this->userApiMock
            ->method('fetchUser')
            ->willReturnCallback(static function (int $userId) use ($user) : UserEntity {
                self::assertSame(42, $userId);

                return $user;
            });
    }

    #[DataProvider('provideSuccessMessages')]
    public function testRenderDataAccountPageConsumesSuccessOnce(FlashMessage $message, string $variable) : void
    {
        $messageConsumed = false;
        $this->flashMessageServiceMock
            ->method('consume')
            ->willReturnCallback(static function (FlashMessage $candidate) use ($message, &$messageConsumed) : bool {
                if ($candidate !== $message || $messageConsumed === true) {
                    return false;
                }

                $messageConsumed = true;

                return true;
            });

        $renderCount = 0;
        $this->twigMock
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template, array $data) use ($variable, &$renderCount) : string {
                self::assertSame('page/settings-account-data.html.twig', $template);
                self::assertSame($renderCount === 0, $data[$variable]);
                self::assertNull($data['importHistoryError']);

                foreach (self::getSuccessVariables() as $successVariable) {
                    if ($successVariable !== $variable) {
                        self::assertFalse($data[$successVariable]);
                    }
                }

                $renderCount++;

                return 'account data';
            });

        $this->subject->renderDataAccountPage();
        $this->subject->renderDataAccountPage();
    }

    #[DataProvider('provideFailureMessages')]
    public function testRenderDataAccountPageConsumesFailureOnce(FlashMessage $message, string $importType) : void
    {
        $messageConsumed = false;
        $this->flashMessageServiceMock
            ->method('consume')
            ->willReturnCallback(static function (FlashMessage $candidate) use ($message, &$messageConsumed) : bool {
                if ($candidate !== $message || $messageConsumed === true) {
                    return false;
                }

                $messageConsumed = true;

                return true;
            });

        $renderCount = 0;
        $this->twigMock
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template, array $data) use ($importType, &$renderCount) : string {
                self::assertSame('page/settings-account-data.html.twig', $template);
                self::assertSame($renderCount === 0 ? $importType : null, $data['importHistoryError']);
                self::assertFalse($data['importHistorySuccessful']);
                self::assertFalse($data['importRatingsSuccessful']);
                self::assertFalse($data['importWatchlistSuccessful']);
                $renderCount++;

                return 'account data';
            });

        $this->subject->renderDataAccountPage();
        $this->subject->renderDataAccountPage();
    }

    /** @return iterable<string, array{FlashMessage, string}> */
    public static function provideSuccessMessages() : iterable
    {
        yield 'history' => [FlashMessage::IMPORT_HISTORY_SUCCESSFUL, 'importHistorySuccessful'];
        yield 'ratings' => [FlashMessage::IMPORT_RATINGS_SUCCESSFUL, 'importRatingsSuccessful'];
        yield 'watchlist' => [FlashMessage::IMPORT_WATCHLIST_SUCCESSFUL, 'importWatchlistSuccessful'];
    }

    /** @return iterable<string, array{FlashMessage, string}> */
    public static function provideFailureMessages() : iterable
    {
        yield 'history' => [FlashMessage::IMPORT_HISTORY_FAILED, 'history'];
        yield 'ratings' => [FlashMessage::IMPORT_RATINGS_FAILED, 'ratings'];
        yield 'watchlist' => [FlashMessage::IMPORT_WATCHLIST_FAILED, 'watchlist'];
    }

    /** @param array<string, object> $dependencies */
    private function createSubject(array $dependencies) : SettingsController
    {
        $reflection = new ReflectionClass(SettingsController::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $dependency = $dependencies[$parameter->getName()] ?? null;
            if ($dependency !== null) {
                $arguments[] = $dependency;

                continue;
            }

            $type = $parameter->getType();
            self::assertInstanceOf(ReflectionNamedType::class, $type);
            $typeName = $type->getName();
            self::assertTrue(class_exists($typeName) || interface_exists($typeName));
            $arguments[] = $this->createMock($typeName);
        }

        $subject = $reflection->newInstanceArgs($arguments);
        self::assertInstanceOf(SettingsController::class, $subject);

        return $subject;
    }

    /** @return array<string> */
    private static function getSuccessVariables() : array
    {
        return [
            'importHistorySuccessful',
            'importRatingsSuccessful',
            'importWatchlistSuccessful',
        ];
    }
}
