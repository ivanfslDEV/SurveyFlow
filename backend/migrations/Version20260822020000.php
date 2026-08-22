<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure that question positions are unique within each survey';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE UNIQUE INDEX uniq_question_survey_position
             ON question (survey_id, position)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_question_survey_position ON question');
    }
}
