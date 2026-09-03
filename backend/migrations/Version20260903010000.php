<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add question options and anonymous survey submissions with answer snapshots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE question_option (
                label VARCHAR(255) NOT NULL,
                position INT NOT NULL,
                id INT AUTO_INCREMENT NOT NULL,
                question_id INT NOT NULL,
                INDEX idx_question_option_question_id (question_id),
                INDEX idx_question_option_position (position),
                UNIQUE INDEX uniq_question_option_position (question_id, position),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE submission (
                survey_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                id INT AUTO_INCREMENT NOT NULL,
                INDEX idx_submission_survey_id (survey_id),
                INDEX idx_submission_created_at (created_at),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE answer (
                question_id INT NOT NULL,
                question_title VARCHAR(255) NOT NULL,
                question_type VARCHAR(30) NOT NULL,
                value JSON NOT NULL,
                id INT AUTO_INCREMENT NOT NULL,
                submission_id INT NOT NULL,
                INDEX idx_answer_submission_id (submission_id),
                INDEX idx_answer_question_id (question_id),
                UNIQUE INDEX uniq_answer_submission_question (submission_id, question_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE question_option
             ADD CONSTRAINT FK_5DDB2FB81E27F6BF
             FOREIGN KEY (question_id) REFERENCES question (id)'
        );
        $this->addSql(
            'ALTER TABLE answer
             ADD CONSTRAINT FK_DADD4A25E1FD4933
             FOREIGN KEY (submission_id) REFERENCES submission (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A25E1FD4933');
        $this->addSql('ALTER TABLE question_option DROP FOREIGN KEY FK_5DDB2FB81E27F6BF');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE question_option');
    }
}
