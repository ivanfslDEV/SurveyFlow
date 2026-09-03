<?php

namespace App\Tests\Functional\Identity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthenticationFlowTest extends WebTestCase
{
    private const PASSWORD = 'StrongPass123!';

    public static function setUpBeforeClass(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $connection = $entityManager->getConnection();
        $connection->insert('survey_status', ['name' => 'draft', 'active' => true]);
        $connection->insert('survey_status', ['name' => 'published', 'active' => true]);
        $connection->insert('survey_status', ['name' => 'archived', 'active' => true]);

        self::ensureKernelShutdown();
    }

    public function testAuthenticationAndSurveyOwnershipFlow(): void
    {
        $client = self::createClient();

        $client->jsonRequest('GET', '/api/surveys');
        self::assertResponseStatusCodeSame(401);

        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => 'alice@example.com',
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);
        $alice = $this->responseData($client);
        self::assertSame('alice@example.com', $alice['email']);
        self::assertSame(['ROLE_USER'], $alice['roles']);
        self::assertArrayNotHasKey('password', $alice);
        self::assertArrayNotHasKey('passwordHash', $alice);

        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => 'ALICE@example.com',
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(409);

        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'alice@example.com',
            'password' => 'WrongPass123!',
        ]);
        self::assertResponseStatusCodeSame(401);

        $aliceToken = $this->login($client, 'alice@example.com');
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$aliceToken);

        $client->jsonRequest('GET', '/api/auth/me');
        self::assertResponseIsSuccessful();
        self::assertSame($alice['id'], $this->responseData($client)['id']);

        $client->jsonRequest('POST', '/api/surveys', [
            'title' => 'Alice survey',
            'description' => 'Private survey',
            'statusName' => 'draft',
        ]);
        self::assertResponseStatusCodeSame(201);
        $survey = $this->responseData($client);
        self::assertSame($alice['id'], $survey['ownerId']);

        $client->jsonRequest('POST', sprintf('/api/surveys/%d/questions', $survey['id']), [[
            'title' => 'How was your experience?',
            'type' => 'rating',
            'required' => true,
            'position' => 1,
        ]]);
        self::assertResponseStatusCodeSame(201);
        $question = $this->responseData($client)[0];

        $client->setServerParameter('HTTP_AUTHORIZATION', '');
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => 'bob@example.com',
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);

        $bobToken = $this->login($client, 'bob@example.com');
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$bobToken);

        $client->jsonRequest('GET', '/api/surveys');
        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->responseData($client)['pagination']['total']);

        $client->jsonRequest('GET', sprintf('/api/surveys/%d', $survey['id']));
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('PATCH', sprintf('/api/surveys/%d', $survey['id']), [
            'title' => 'Unauthorized change',
        ]);
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('PATCH', sprintf('/api/surveys/%d/status', $survey['id']), [
            'statusName' => 'published',
        ]);
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('POST', sprintf('/api/surveys/%d/questions', $survey['id']), [[
            'title' => 'Unauthorized question',
            'type' => 'text',
            'required' => false,
            'position' => 2,
        ]]);
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('GET', sprintf('/api/surveys/%d/questions', $survey['id']));
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('PATCH', sprintf('/api/questions/%d', $question['id']), [
            'title' => 'Unauthorized change',
        ]);
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('DELETE', sprintf('/api/questions/%d', $question['id']));
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('DELETE', sprintf('/api/surveys/%d', $survey['id']));
        self::assertResponseStatusCodeSame(403);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$aliceToken);
        $client->jsonRequest('GET', sprintf('/api/surveys/%d', $survey['id']));
        self::assertResponseIsSuccessful();

        $client->jsonRequest('GET', sprintf('/api/surveys/%d/questions', $survey['id']));
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->responseData($client)['pagination']['total']);
    }

    private function login(KernelBrowser $client, string $email): string
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseIsSuccessful();

        $data = $this->responseData($client);
        self::assertArrayHasKey('token', $data);
        self::assertNotSame('', $data['token']);

        return $data['token'];
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(KernelBrowser $client): array
    {
        return json_decode(
            (string) $client->getResponse()->getContent(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
