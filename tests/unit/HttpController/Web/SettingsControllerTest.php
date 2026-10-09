<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\SettingsController;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\RelativeUrl;
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
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

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
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = $this->createSubject([
            'twig' => $this->twigMock,
            'authenticationService' => $this->authenticationMock,
            'userApi' => $this->userApiMock,
            'flashMessageService' => $this->flashMessageServiceMock,
            'applicationUrlService' => $this->applicationUrlServiceMock,
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

    #[DataProvider('provideLetterboxdMessages')]
    public function testRenderLetterboxdPageConsumesMessageOnce(FlashMessage $message, string $variable) : void
    {
        $this->assertIntegrationMessageConsumedOnce(
            $message,
            $variable,
            'page/settings-integration-letterboxd.html.twig',
            self::getLetterboxdVariables(),
            fn() => $this->subject->renderLetterboxdPage(),
        );
    }

    #[DataProvider('provideTraktMessages')]
    public function testRenderTraktPageConsumesMessageOnce(FlashMessage $message, string $variable) : void
    {
        $this->assertIntegrationMessageConsumedOnce(
            $message,
            $variable,
            'page/settings-integration-trakt.html.twig',
            self::getTraktVariables(),
            fn() => $this->subject->renderTraktPage(),
        );
    }

    public function testUpdateTraktAddsCredentialsUpdatedMessage() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'traktClientId' => 'client-id',
            'traktUserName' => 'username',
        ]);
        $this->userApiMock->expects(self::once())->method('updateTraktClientId')->with(42, 'client-id');
        $this->userApiMock->expects(self::once())->method('updateTraktUserName')->with(42, 'username');
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::TRAKT_CREDENTIALS_UPDATED);
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(static fn(RelativeUrl $url) => (string)$url === '/settings/integrations/trakt'))
            ->willReturn('/movary/settings/integrations/trakt');

        $response = $this->subject->updateTrakt($request);

        self::assertSame(303, $response->getStatusCode()->getCode());
        self::assertSame('Location: /movary/settings/integrations/trakt', (string)$response->getHeaders()[0]);
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

    /** @return iterable<string, array{FlashMessage, string}> */
    public static function provideLetterboxdMessages() : iterable
    {
        yield 'diary scheduled' => [
            FlashMessage::LETTERBOXD_DIARY_SYNC_SCHEDULED,
            'letterboxdDiarySyncSuccessful',
        ];
        yield 'ratings scheduled' => [
            FlashMessage::LETTERBOXD_RATINGS_SYNC_SCHEDULED,
            'letterboxdRatingsSyncSuccessful',
        ];
        yield 'ratings invalid' => [
            FlashMessage::LETTERBOXD_RATINGS_FILE_INVALID,
            'letterboxdRatingsImportFileInvalid',
        ];
        yield 'diary invalid' => [
            FlashMessage::LETTERBOXD_DIARY_FILE_INVALID,
            'letterboxdDiaryImportFileInvalid',
        ];
    }

    /** @return iterable<string, array{FlashMessage, string}> */
    public static function provideTraktMessages() : iterable
    {
        yield 'credentials updated' => [FlashMessage::TRAKT_CREDENTIALS_UPDATED, 'traktCredentialsUpdated'];
        yield 'history scheduled' => [
            FlashMessage::TRAKT_HISTORY_IMPORT_SCHEDULED,
            'traktScheduleHistorySyncSuccessful',
        ];
        yield 'ratings scheduled' => [
            FlashMessage::TRAKT_RATINGS_IMPORT_SCHEDULED,
            'traktScheduleRatingsSyncSuccessful',
        ];
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

    /**
     * @param array<string> $variables
     * @param callable(): mixed $renderPage
     */
    private function assertIntegrationMessageConsumedOnce(
        FlashMessage $message,
        string $variable,
        string $template,
        array $variables,
        callable $renderPage,
    ) : void {
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
            ->willReturnCallback(
                static function (string $renderedTemplate, array $data) use (
                    $template,
                    $variable,
                    $variables,
                    &$renderCount,
                ) : string {
                    self::assertSame($template, $renderedTemplate);
                    self::assertSame($renderCount === 0, $data[$variable]);

                    foreach ($variables as $otherVariable) {
                        if ($otherVariable !== $variable) {
                            self::assertFalse($data[$otherVariable]);
                        }
                    }

                    $renderCount++;

                    return 'integration';
                },
            );

        $renderPage();
        $renderPage();
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

    /** @return array<string> */
    private static function getLetterboxdVariables() : array
    {
        return [
            'letterboxdDiarySyncSuccessful',
            'letterboxdRatingsSyncSuccessful',
            'letterboxdRatingsImportFileInvalid',
            'letterboxdDiaryImportFileInvalid',
        ];
    }

    /** @return array<string> */
    private static function getTraktVariables() : array
    {
        return [
            'traktCredentialsUpdated',
            'traktScheduleHistorySyncSuccessful',
            'traktScheduleRatingsSyncSuccessful',
        ];
    }
}
