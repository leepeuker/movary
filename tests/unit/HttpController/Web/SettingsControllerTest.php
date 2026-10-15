<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\Service\EmailVerificationPolicy;
use Movary\Domain\User\Service\PersonalApiTokenService;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\SettingsController;
use Movary\HttpController\Web\Mapper\PaginationRequestMapper;
use Movary\HttpController\Web\Mapper\JobScheduleFilterRequestMapper;
use Movary\HttpController\Web\Mapper\UserFilterRequestMapper;
use Movary\JobQueue\JobScheduleFilter;
use Movary\JobQueue\JobScheduleApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Dashboard\Dto\DashboardRowList;
use Movary\Service\Email\EmailSupport;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\MovieTitleDisplay;
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

    private JobScheduleApi|MockObject $jobScheduleApiMock;

    private DashboardFactory|MockObject $dashboardFactoryMock;

    private PersonalApiTokenService|MockObject $personalApiTokenServiceMock;

    private SettingsController $subject;

    private Environment|MockObject $twigMock;

    private UserApi|MockObject $userApiMock;

    private CurrentWebUser|MockObject $currentWebUserMock;

    private UserEntity|MockObject $userMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->currentWebUserMock = $this->createMock(CurrentWebUser::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->jobScheduleApiMock = $this->createMock(JobScheduleApi::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->dashboardFactoryMock = $this->createMock(DashboardFactory::class);
        $this->personalApiTokenServiceMock = $this->createMock(PersonalApiTokenService::class);
        $this->subject = $this->createSubject([
            'twig' => $this->twigMock,
            'authenticationService' => $this->authenticationMock,
            'userApi' => $this->userApiMock,
            'flashMessageService' => $this->flashMessageServiceMock,
            'jobScheduleApi' => $this->jobScheduleApiMock,
            'applicationUrlService' => $this->applicationUrlServiceMock,
            'dashboardFactory' => $this->dashboardFactoryMock,
            'personalApiTokenService' => $this->personalApiTokenServiceMock,
            'paginationRequestMapper' => new PaginationRequestMapper(),
            'jobScheduleFilterRequestMapper' => new JobScheduleFilterRequestMapper(),
            'paginationElementsCalculator' => new PaginationElementsCalculator(),
            'currentWebUser' => $this->currentWebUserMock,
        ]);

        $this->userMock = $this->createMock(UserEntity::class);
        $this->userMock->method('getId')->willReturn(42);
        $this->userMock->method('hasCoreAccountChangesDisabled')->willReturn(false);
        $this->authenticationMock->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $this->currentWebUserMock->method('requireUser')->willReturn($this->userMock);
    }

    public function testRenderServerJobSchedulesPageRendersSchedules() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn([]);
        $schedules = [['id' => 9, 'type' => 'tmdb_movie_sync']];
        $this->jobScheduleApiMock->expects(self::once())->method('countSchedules')->willReturn(1);
        $this->jobScheduleApiMock
            ->expects(self::once())
            ->method('fetchForStatusPage')
            ->with(
                20,
                0,
                self::callback(static fn(JobScheduleFilter $filter) : bool => $filter->hasFilters() === false),
            )
            ->willReturn($schedules);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/settings-server-job-schedules.html.twig',
                self::callback(static fn(array $data) : bool => $data['jobSchedules'] === $schedules
                    && $data['jobSchedulesPerPage'] === 20
                    && $data['paginationQuery'] === ['perPage' => 20]
                    && $data['jobScheduleFiltersActive'] === false),
            )
            ->willReturn('job schedules');

        $response = $this->subject->renderServerJobSchedulesPage($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('job schedules', $response->getBody());
    }

    public function testRenderSecurityAccountPageProvidesEmailChangeState() : void
    {
        $this->userMock->method('getEmail')->willReturn('current@example.com');
        $this->userMock->method('getPendingEmail')->willReturn('pending@example.com');
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/settings-account-security.html.twig',
                self::callback(
                    static fn(array $data) : bool => $data['email'] === 'current@example.com'
                        && $data['pendingEmail'] === 'pending@example.com'
                        && $data['emailVerificationEnabled'] === false,
                ),
            );

        $this->subject->renderSecurityAccountPage();
    }

    #[DataProvider('provideEmailVerificationConfiguration')]
    public function testRenderServerUsersPageProvidesCompleteEmailVerificationState(
        bool $verificationRequired,
        bool $emailVerificationAvailable,
        bool $expectedEnabled,
    ) : void {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn([]);
        $policy = $this->createStub(EmailVerificationPolicy::class);
        $policy->method('isRequired')->willReturn($verificationRequired);
        $emailSupport = $this->createStub(EmailSupport::class);
        $emailSupport->method('isEmailVerificationAvailable')->willReturn($emailVerificationAvailable);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/settings-server-users.html.twig',
                self::callback(
                    static fn(array $data) : bool => $data['emailVerificationEnabled'] === $expectedEnabled,
                ),
            );
        $subject = $this->createSubject([
            'twig' => $this->twigMock,
            'userApi' => $this->userApiMock,
            'emailSupport' => $emailSupport,
            'emailVerificationPolicy' => $policy,
            'paginationRequestMapper' => new PaginationRequestMapper(),
            'paginationElementsCalculator' => new PaginationElementsCalculator(),
            'userFilterRequestMapper' => new UserFilterRequestMapper(),
        ]);

        $subject->renderServerUsersPage($request);
    }

    public static function provideEmailVerificationConfiguration() : iterable
    {
        yield 'policy and email support enabled' => [true, true, true];
        yield 'email support unavailable' => [true, false, false];
        yield 'policy disabled' => [false, true, false];
        yield 'both disabled' => [false, false, false];
    }

    public function testUpdatePasswordUsesDomainPolicyAndPreservesBadRequestContract() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'currentPassword' => 'legacy',
            'newPassword' => 'too short',
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('isValidPassword')
            ->with(42, 'legacy')
            ->willReturn(true);
        $this->userApiMock
            ->expects(self::once())
            ->method('updatePassword')
            ->with(42, 'too short')
            ->willThrowException(new PasswordTooShort(15));
        $this->authenticationMock->expects(self::never())->method('logout');

        $response = $this->subject->updatePassword($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame('New password not meeting requirements', $response->getBody());
    }

    public function testUpdatePasswordLogsOutAfterSuccessfulDomainUpdate() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'currentPassword' => 'legacy',
            'newPassword' => 'a secure password',
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('isValidPassword')
            ->with(42, 'legacy')
            ->willReturn(true);
        $this->userApiMock
            ->expects(self::once())
            ->method('updatePassword')
            ->with(42, 'a secure password');
        $this->authenticationMock->expects(self::once())->method('logout');

        self::assertSame(200, $this->subject->updatePassword($request)->getStatusCode()->getCode());
    }

    public function testCreatesPersonalApiToken() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'name' => 'Home automation',
            'expirationDays' => 90,
        ]));
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('createToken')
            ->with(42, 'Home automation', 90)
            ->willReturn([
                'token' => 'mvy_pat_v1_token',
                'expiresAt' => DateTime::createFromString('2027-01-08 12:00:00'),
            ]);

        $response = $this->subject->createApiToken($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame([
            'token' => 'mvy_pat_v1_token',
            'expiresAt' => '2027-01-08 12:00:00',
        ], Json::decode((string)$response->getBody()));
        self::assertSame(
            ['Content-Type: application/json', 'Cache-Control: no-store', 'Pragma: no-cache'],
            array_map('strval', $response->getHeaders()),
        );
    }

    public function testUpdatesMovieTitleDisplayPreference() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'username' => 'movary',
            'movieTitleDisplay' => 'original',
        ]));
        $this->userApiMock
            ->expects(self::once())
            ->method('updateMovieTitleDisplay')
            ->with(
                42,
                self::callback(
                    static fn(MovieTitleDisplay $display) : bool => (string)$display === 'original',
                ),
            );

        self::assertSame(200, $this->subject->updateGeneral($request)->getStatusCode()->getCode());
    }

    public function testRejectsInvalidMovieTitleDisplayPreference() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'username' => 'movary',
            'movieTitleDisplay' => 'invalid',
        ]));
        $this->userApiMock->expects(self::never())->method('updateMovieTitleDisplay');

        self::assertSame(400, $this->subject->updateGeneral($request)->getStatusCode()->getCode());
    }

    public function testRejectsMissingMovieTitleDisplayPreference() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn(Json::encode([
            'username' => 'movary',
        ]));
        $this->userApiMock->expects(self::never())->method('updateMovieTitleDisplay');

        self::assertSame(400, $this->subject->updateGeneral($request)->getStatusCode()->getCode());
    }

    public function testRevokesOwnedPersonalApiToken() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['tokenId' => '7']);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('revokeToken')
            ->with(42, 7);

        self::assertSame(200, $this->subject->revokeApiToken($request)->getStatusCode()->getCode());
    }

    public function testRevokesAllPersonalApiTokens() : void
    {
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('revokeAllTokens')
            ->with(42);

        self::assertSame(200, $this->subject->revokeAllApiTokens()->getStatusCode()->getCode());
    }

    public function testRendersPaginatedPersonalApiTokens() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn(['page' => '2', 'perPage' => '50']);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('countTokens')
            ->with(42)
            ->willReturn(70);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('fetchTokensPaginated')
            ->with(42, 50, 50)
            ->willReturn([]);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/settings-account-api-tokens.html.twig',
                self::callback(static function (array $data) : bool {
                    self::assertSame([], $data['apiTokens']);
                    self::assertSame(50, $data['apiTokensPerPage']);
                    self::assertSame(70, $data['paginationElements']->getTotalCount());
                    self::assertSame(['perPage' => 50], $data['paginationQuery']);

                    return true;
                }),
            )
            ->willReturn('api tokens');

        $response = $this->subject->renderApiTokensAccountPage($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame('api tokens', $response->getBody());
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

    public function testResetDashboardRowsAddsMessage() : void
    {
        $this->userApiMock->expects(self::once())->method('updateVisibleDashboardRows')->with(42, null);
        $this->userApiMock->expects(self::once())->method('updateExtendedDashboardRows')->with(42, null);
        $this->userApiMock->expects(self::once())->method('updateOrderDashboardRows')->with(42, null);
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::DASHBOARD_ROWS_RESET);

        self::assertSame(200, $this->subject->resetDashboardRows()->getStatusCode()->getCode());
    }

    public function testUpdateMastodonAddsMessage() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'mastodonEnable' => 'on',
            'mastodonOnByDefault' => 'on',
            'mastodonUsername' => ' user ',
            'mastodonAccessToken' => ' token ',
            'mastodonVisibility' => 'private',
        ]);
        $this->userApiMock->expects(self::once())->method('updateMastodonPostEnabled')->with(42, true);
        $this->userApiMock->expects(self::once())->method('updateMastodonUsername')->with(42, 'user');
        $this->userApiMock->expects(self::once())->method('updateMastodonAccessToken')->with(42, 'token');
        $this->userApiMock->expects(self::once())->method('updateMastodonPostAutomatic')->with(42, true);
        $this->userApiMock->expects(self::once())->method('updateMastodonPostVisibility')->with(42, 'private');
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::MASTODON_CREDENTIALS_UPDATED);
        $this->applicationUrlServiceMock->method('createApplicationUrl')->willReturn('/movary/settings/mastodon');

        $response = $this->subject->updateMastodon($request);

        self::assertSame(303, $response->getStatusCode()->getCode());
    }

    public function testRenderDashboardPageConsumesResetMessageOnce() : void
    {
        $this->dashboardFactoryMock
            ->expects(self::exactly(2))
            ->method('createDashboardRowsForUser')
            ->willReturn(DashboardRowList::create());

        $this->assertIntegrationMessageConsumedOnce(
            FlashMessage::DASHBOARD_ROWS_RESET,
            'dashboardRowsSuccessfullyReset',
            'page/settings-account-dashboard.html.twig',
            ['dashboardRowsSuccessfullyReset'],
            fn() => $this->subject->renderDashboardAccountPage(),
        );
    }

    public function testRenderMastodonPageConsumesCredentialsMessageOnce() : void
    {
        $this->assertIntegrationMessageConsumedOnce(
            FlashMessage::MASTODON_CREDENTIALS_UPDATED,
            'mastodonCredentialsUpdated',
            'page/settings-integration-mastodon.html.twig',
            ['mastodonCredentialsUpdated'],
            fn() => $this->subject->renderMastodonPage(),
        );
    }

    #[DataProvider('provideTwoFactorMessages')]
    public function testRenderSecurityPageConsumesTwoFactorMessageOnce(FlashMessage $message, string $variable) : void
    {
        $this->assertIntegrationMessageConsumedOnce(
            $message,
            $variable,
            'page/settings-account-security.html.twig',
            ['twoFactorAuthenticationEnabled', 'twoFactorAuthenticationDisabled'],
            fn() => $this->subject->renderSecurityAccountPage(),
        );
    }

    /** @return iterable<string, array{FlashMessage, string}> */
    public static function provideTwoFactorMessages() : iterable
    {
        yield 'enabled' => [FlashMessage::TWO_FACTOR_AUTHENTICATION_ENABLED, 'twoFactorAuthenticationEnabled'];
        yield 'disabled' => [FlashMessage::TWO_FACTOR_AUTHENTICATION_DISABLED, 'twoFactorAuthenticationDisabled'];
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
