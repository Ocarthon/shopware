<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\System\User\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\User\Service\UserValidationService;
use Shopware\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('fundamentals@framework')]
class UserValidationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $userRepository;

    private EntityRepository $localeRepository;

    private UserValidationService $userValidationService;

    protected function setUp(): void
    {
        $this->userRepository = static::getContainer()->get('user.repository');
        $this->localeRepository = static::getContainer()->get('locale.repository');
        $this->userValidationService = static::getContainer()->get(UserValidationService::class);
    }

    public function testIfReturnsTrueForUniqueEmails(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@shopware.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertTrue($this->userValidationService->checkEmailUnique('some@other.email', $userIdToTest, $context));
        static::assertTrue($this->userValidationService->checkEmailUnique('user@shopware.com', $userId, $context));
    }

    public function testIfReturnsFalseForDuplicateEmails(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();

        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@shopware.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertFalse($this->userValidationService->checkEmailUnique('user@shopware.com', $userIdToTest, $context));
    }

    public function testIfReturnsTrueForUniqueUsernames(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@shopware.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertTrue($this->userValidationService->checkUsernameUnique('other User', $userIdToTest, $context));
        static::assertTrue($this->userValidationService->checkUsernameUnique('some User', $userId, $context));
    }

    public function testIfReturnsFalseForDuplicateUsernames(): void
    {
        $userId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $localeIds = $this->localeRepository->searchIds(new Criteria(), $context)->getIds();
        $firstLocale = array_pop($localeIds);

        $this->userRepository->create([
            [
                'id' => $userId,
                'username' => 'some User',
                'firstName' => 'first',
                'lastName' => 'last',
                'localeId' => $firstLocale,
                'email' => 'user@shopware.com',
                'password' => TestDefaults::HASHED_PASSWORD,
            ],
        ], $context);

        $userIdToTest = Uuid::randomHex();
        static::assertFalse($this->userValidationService->checkUsernameUnique('some User', $userIdToTest, $context));
    }
}
