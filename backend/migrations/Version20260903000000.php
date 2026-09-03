<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and assign an owner to every survey';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE users (
                email VARCHAR(180) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                active TINYINT DEFAULT 1 NOT NULL,
                created_at DATETIME NOT NULL,
                id INT AUTO_INCREMENT NOT NULL,
                UNIQUE INDEX uniq_users_email (email),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql('ALTER TABLE survey ADD owner_id INT DEFAULT NULL');

        // Existing surveys are preserved under an inactive, non-login account.
        $this->addSql(
            "INSERT INTO users (email, password_hash, roles, active, created_at)
             SELECT 'legacy@surveyflow.local', '!', JSON_ARRAY('ROLE_USER'), 0, NOW()
             WHERE EXISTS (SELECT 1 FROM survey)"
        );
        $this->addSql(
            "UPDATE survey
             SET owner_id = (SELECT id FROM users WHERE email = 'legacy@surveyflow.local' LIMIT 1)
             WHERE owner_id IS NULL"
        );

        $this->addSql('ALTER TABLE survey MODIFY owner_id INT NOT NULL');
        $this->addSql('CREATE INDEX idx_survey_owner_id ON survey (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_survey_owner_id ON survey');
        $this->addSql('ALTER TABLE survey DROP owner_id');
        $this->addSql('DROP TABLE users');
    }
}
