<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\ServerEmailRequestMapper;
use Movary\Service\Email\EmailSettingsConflict;
use Movary\Service\Email\InvalidSmtpConfigException;
use Movary\Service\Email\ServerEmailSettingsService;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class ServerEmailController
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
        private readonly ServerEmailSettingsService $emailSettingsService,
        private readonly ServerEmailRequestMapper $requestMapper,
    ) {
    }

    public function get() : Response
    {
        return Response::createSensitiveJson(Json::encode([
            'emailEnabled' => $this->serverSettings->isEmailEnabled(),
            'smtpHost' => $this->serverSettings->getSmtpHost(),
            'smtpPort' => $this->serverSettings->getSmtpPort(),
            'smtpFromAddress' => $this->serverSettings->getFromAddress(),
            'smtpEncryption' => $this->serverSettings->getSmtpEncryption(),
            'smtpWithAuthentication' => $this->serverSettings->getSmtpWithAuthentication(),
            'smtpUserConfigured' => strlen((string)$this->serverSettings->getSmtpUser()) > 0,
            'smtpPasswordConfigured' => strlen((string)$this->serverSettings->getSmtpPassword()) > 0,
            'environmentControlledFields' => $this->emailSettingsService->getEnvironmentControlledFields(),
        ]));
    }

    public function update(Request $request) : Response
    {
        $data = $this->requestMapper->mapUpdate($request);
        if (array_intersect(array_keys($data), $this->emailSettingsService->getEnvironmentControlledFields()) !== []) {
            return $this->conflict('Fields controlled by environment variables cannot be updated.');
        }

        try {
            $this->emailSettingsService->update($data);
        } catch (InvalidSmtpConfigException $e) {
            throw InvalidApiRequest::invalidField('email', $e->getMessage());
        } catch (EmailSettingsConflict $e) {
            return $this->conflict($e->getMessage());
        }

        return Response::createSensitive(StatusCode::createNoContent());
    }

    public function delete() : Response
    {
        try {
            $this->emailSettingsService->delete();
        } catch (EmailSettingsConflict $e) {
            return $this->conflict($e->getMessage());
        }

        return Response::createSensitive(StatusCode::createNoContent());
    }

    private function conflict(string $message) : Response
    {
        return Response::createSensitiveJson(Json::encode([
            'error' => 'EmailSettingsConflict',
            'message' => $message,
        ]), StatusCode::createConflict());
    }
}
