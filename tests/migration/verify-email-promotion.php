<?php declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;

$container = require(__DIR__ . '/../../bootstrap.php');
$connection = $container->get(Connection::class);
$repository = new UserRepository($connection);
$verificationDate = DateTime::createFromString('2026-10-15 12:00:00');

$repository->createUser(
    'email-promotion-old@example.com',
    'password-hash',
    'EmailPromotionAudit',
    false,
    true,
    DateTime::createFromString('2026-10-14 12:00:00'),
);
$userId = (int)$connection->lastInsertId();
$repository->updatePendingEmail($userId, 'email-promotion-new@example.com');
$repository->createAuthToken(
    $userId,
    '0123456789abcdef0123456789abcdef',
    'test-device',
    'test-agent',
    DateTime::createFromString('2026-10-16 12:00:00'),
);
$repository->createPersonalApiToken(
    $userId,
    'test-token',
    str_repeat('a', 64),
    'mvy_test',
    DateTime::createFromString('2026-10-14 12:00:00'),
    null,
);
$repository->replaceEmailVerificationToken(
    $userId,
    'email-promotion-new@example.com',
    str_repeat('b', 64),
    DateTime::createFromString('2026-10-16 12:00:00'),
);

if ($repository->verifyEmailWithToken(str_repeat('b', 64), $verificationDate) === false) {
    throw new RuntimeException('Pending email verification failed.');
}

$user = $connection->fetchAssociative(
    'SELECT email, pending_email, email_verified_at FROM `user` WHERE id = ?',
    [$userId],
);
if ($user === false
    || $user['email'] !== 'email-promotion-new@example.com'
    || $user['pending_email'] !== null
    || $user['email_verified_at'] !== (string)$verificationDate
) {
    throw new RuntimeException('Pending email was not promoted atomically.');
}
if ((int)$connection->fetchOne('SELECT COUNT(*) FROM user_email_verification_token WHERE user_id = ?', [$userId]) !== 0
    || (int)$connection->fetchOne('SELECT COUNT(*) FROM user_auth_token WHERE user_id = ?', [$userId]) !== 0
    || (int)$connection->fetchOne('SELECT COUNT(*) FROM user_api_token WHERE user_id = ?', [$userId]) !== 0
) {
    throw new RuntimeException('Email promotion did not revoke all credentials.');
}

$repository->createUser(
    'admin-approval-old@example.com',
    'password-hash',
    'AdminApprovalAudit',
    false,
    true,
    DateTime::createFromString('2026-10-14 12:00:00'),
);
$approvedUserId = (int)$connection->lastInsertId();
$repository->updatePendingEmail($approvedUserId, 'admin-approval-new@example.com');
$repository->createAuthToken(
    $approvedUserId,
    'abcdef0123456789abcdef0123456789',
    'test-device',
    'test-agent',
    DateTime::createFromString('2026-10-16 12:00:00'),
);
$repository->createPersonalApiToken(
    $approvedUserId,
    'test-token',
    str_repeat('c', 64),
    'mvy_test',
    DateTime::createFromString('2026-10-14 12:00:00'),
    null,
);
$repository->replaceEmailVerificationToken(
    $approvedUserId,
    'admin-approval-new@example.com',
    str_repeat('d', 64),
    DateTime::createFromString('2026-10-16 12:00:00'),
);

if ($repository->approvePendingEmail($approvedUserId, $verificationDate) === false) {
    throw new RuntimeException('Administrative pending email approval failed.');
}

$approvedUser = $connection->fetchAssociative(
    'SELECT email, pending_email, email_verified_at FROM `user` WHERE id = ?',
    [$approvedUserId],
);
if ($approvedUser === false
    || $approvedUser['email'] !== 'admin-approval-new@example.com'
    || $approvedUser['pending_email'] !== null
    || $approvedUser['email_verified_at'] !== (string)$verificationDate
) {
    throw new RuntimeException('Administrative approval did not promote the pending email atomically.');
}
if ((int)$connection->fetchOne(
    'SELECT COUNT(*) FROM user_email_verification_token WHERE user_id = ?',
    [$approvedUserId],
) !== 0
    || (int)$connection->fetchOne('SELECT COUNT(*) FROM user_auth_token WHERE user_id = ?', [$approvedUserId]) !== 0
    || (int)$connection->fetchOne('SELECT COUNT(*) FROM user_api_token WHERE user_id = ?', [$approvedUserId]) !== 0
) {
    throw new RuntimeException('Administrative approval did not revoke all credentials.');
}
