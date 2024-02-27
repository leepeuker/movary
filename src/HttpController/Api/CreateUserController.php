<?php

namespace Movary\HttpController\Api;

use Movary\Domain\User\Exception\EmailNotUnique;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Exception\UsernameInvalidFormat;
use Movary\Domain\User\Exception\UsernameNotUnique;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Exception;

class CreateUserController
{
    public const STRING MOVARY_WEB_CLIENT = 'Movary Web';
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createUser(Request $request) : Response
    {
        $hasUsers = $this->userApi->hasUsers();
        $jsonData = Json::decode($request->getBody());

        $deviceName = $request->getHeaders()['X-Movary-Client'] ?? null;
        if(empty($deviceName)) {
            return Response::createBadRequest('No client header');
        }
        $userAgent = $request->getUserAgent();
        
        $email = empty($jsonData['email']) === true ? null : (string)$jsonData['email'];
        $username = empty($jsonData['username']) === true ? null : (string)$jsonData['username'];
        $password = empty($jsonData['password']) === true ? null : (string)$jsonData['password'];
        $repeatPassword = empty($jsonData['repeatPassword']) === true ? null : (string)$jsonData['repeatPassword'];

        if ($email === null || $username === null || $password === null || $repeatPassword === null) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingInput',
                    'message' => 'Email, username, password or the password repeat is missing'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        if ($password !== $repeatPassword) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'PasswordsNotEqual',
                    'message' => 'The repeated password is not the same as the password'
                ]),
                [Header::createContentTypeJson()],
            );
        }

        try {
            $this->userApi->createUser($email, $password, $username, $hasUsers === false);
            $userAndAuthToken = $this->authenticationService->login($email, $password, false, $deviceName, $userAgent);

            return Response::createJson(
                Json::encode([
                    'userId' => $userAndAuthToken['user']->getId(),
                    'authToken' => $userAndAuthToken['token']
                ]),
            );
        } catch (UsernameInvalidFormat) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'UsernameInvalidFormat',
                    'message' => 'Username can only contain letters or numbers'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (UsernameNotUnique) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'UsernameNotUnique',
                    'message' => 'Username is already taken'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (EmailNotUnique) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'EmailNotUnique',
                    'message' => 'Email is already taken'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch(PasswordTooShort) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'PasswordTooShort',
                    'message' => 'Password must be at least 8 characters'
                ]),
                [Header::createContentTypeJson()],
            );
        } catch (Exception) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'GenericError',
                    'message' => 'Something has gone wrong. Please check the logs and try again later.'
                ]),
                [Header::createContentTypeJson()],
            );
        }
    }
}
