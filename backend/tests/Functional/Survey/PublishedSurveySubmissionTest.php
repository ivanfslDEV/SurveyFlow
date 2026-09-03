<?php

namespace App\Tests\Functional\Survey;

use App\Tests\Functional\DatabaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PublishedSurveySubmissionTest extends DatabaseWebTestCase
{
    private const PASSWORD = 'StrongPass123!';

    public function testAnonymousSubmissionAndOwnerOnlyResults(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerAndLogin($client, 'owner@example.com');
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$ownerToken);

        $client->jsonRequest('POST', '/api/surveys', [
            'title' => 'Published customer survey',
            'description' => 'Tell us about your experience',
            'statusName' => 'draft',
        ]);
        self::assertResponseStatusCodeSame(201);
        $survey = $this->responseData($client);

        $client->jsonRequest('POST', sprintf('/api/surveys/%d/questions', $survey['id']), [
            [
                'title' => 'What is your name?',
                'type' => 'text',
                'required' => true,
                'position' => 1,
            ],
            [
                'title' => 'How do you rate us?',
                'type' => 'rating',
                'required' => false,
                'position' => 2,
            ],
            [
                'title' => 'Which plan do you prefer?',
                'type' => 'single_choice',
                'required' => true,
                'position' => 3,
                'options' => [
                    ['label' => 'Basic', 'position' => 1],
                    ['label' => 'Premium', 'position' => 2],
                ],
            ],
            [
                'title' => 'How should we contact you?',
                'type' => 'multiple_choice',
                'required' => true,
                'position' => 4,
                'options' => [
                    ['label' => 'Email', 'position' => 1],
                    ['label' => 'SMS', 'position' => 2],
                    ['label' => 'Push notification', 'position' => 3],
                ],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);
        $questions = $this->responseData($client);
        $textQuestion = $questions[0];
        $ratingQuestion = $questions[1];
        $choiceQuestion = $questions[2];
        $premiumOption = $choiceQuestion['options'][1];
        $multipleChoiceQuestion = $questions[3];
        $emailOption = $multipleChoiceQuestion['options'][0];
        $pushOption = $multipleChoiceQuestion['options'][2];

        $client->setServerParameter('HTTP_AUTHORIZATION', '');
        $client->jsonRequest('GET', sprintf('/api/public/surveys/%d', $survey['id']));
        self::assertResponseStatusCodeSame(404);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$ownerToken);
        $client->jsonRequest('PATCH', sprintf('/api/surveys/%d/status', $survey['id']), [
            'statusName' => 'published',
        ]);
        self::assertResponseIsSuccessful();

        $client->setServerParameter('HTTP_AUTHORIZATION', '');
        $client->jsonRequest('GET', sprintf('/api/public/surveys/%d', $survey['id']));
        self::assertResponseIsSuccessful();
        $publicSurvey = $this->responseData($client);
        self::assertSame('published', $publicSurvey['status']);
        self::assertCount(4, $publicSurvey['questions']);
        self::assertCount(2, $publicSurvey['questions'][2]['options']);

        $client->jsonRequest('POST', sprintf('/api/public/surveys/%d/submissions', $survey['id']), [
            'answers' => [[
                'questionId' => $textQuestion['id'],
                'value' => 'Anonymous person',
            ]],
        ]);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', sprintf('/api/public/surveys/%d/submissions', $survey['id']), [
            'answers' => [
                ['questionId' => $textQuestion['id'], 'value' => 'Anonymous person'],
                ['questionId' => $ratingQuestion['id'], 'value' => 6],
                ['questionId' => $choiceQuestion['id'], 'value' => $premiumOption['id']],
                [
                    'questionId' => $multipleChoiceQuestion['id'],
                    'value' => [$emailOption['id'], $pushOption['id']],
                ],
            ],
        ]);
        self::assertResponseStatusCodeSame(422);

        $client->jsonRequest('POST', sprintf('/api/public/surveys/%d/submissions', $survey['id']), [
            'answers' => [
                ['questionId' => $textQuestion['id'], 'value' => 'Anonymous person'],
                ['questionId' => $ratingQuestion['id'], 'value' => 5],
                ['questionId' => $choiceQuestion['id'], 'value' => $premiumOption['id']],
                [
                    'questionId' => $multipleChoiceQuestion['id'],
                    'value' => [$emailOption['id'], $pushOption['id']],
                ],
            ],
        ]);
        self::assertResponseStatusCodeSame(201);
        $submission = $this->responseData($client);
        self::assertSame($survey['id'], $submission['surveyId']);
        self::assertCount(4, $submission['answers']);
        self::assertSame('Premium', $submission['answers'][2]['value']['label']);
        self::assertSame(
            ['Email', 'Push notification'],
            array_column($submission['answers'][3]['value'], 'label'),
        );

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$ownerToken);
        $client->jsonRequest('GET', sprintf('/api/surveys/%d/submissions', $survey['id']));
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->responseData($client)['pagination']['total']);

        $client->jsonRequest('GET', sprintf('/api/submissions/%d', $submission['id']));
        self::assertResponseIsSuccessful();
        self::assertSame($submission['id'], $this->responseData($client)['id']);

        $otherToken = $this->registerAndLogin($client, 'other@example.com');
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$otherToken);

        $client->jsonRequest('GET', sprintf('/api/surveys/%d/submissions', $survey['id']));
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('GET', sprintf('/api/submissions/%d', $submission['id']));
        self::assertResponseStatusCodeSame(403);
    }

    private function registerAndLogin(KernelBrowser $client, string $email): string
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', '');
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => $email,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseIsSuccessful();

        return $this->responseData($client)['token'];
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
