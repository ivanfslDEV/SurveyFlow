<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create questions with survey, position, and foreign-key indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE question (
                id INT AUTO_INCREMENT NOT NULL,
                survey_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                type VARCHAR(30) NOT NULL,
                required TINYINT(1) NOT NULL,
                position INT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_question_survey_id (survey_id),
                INDEX idx_question_position (position),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE question
             ADD CONSTRAINT FK_4F812B18B3FE509D
             FOREIGN KEY (survey_id) REFERENCES survey (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_4F812B18B3FE509D');
        $this->addSql('DROP TABLE question');
    }
}
