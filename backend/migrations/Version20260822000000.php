<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the active flag used to soft-delete surveys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survey ADD active TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survey DROP active');
    }
}
