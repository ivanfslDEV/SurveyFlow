<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
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
}
