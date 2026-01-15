<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115185155 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE users (
                id CHAR(36) NOT NULL,
                created_at DATETIME DEFAULT NULL,
                email VARCHAR(180) NOT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) NOT NULL,
                is_verified TINYINT NOT NULL,
                currently_active_organizations_id CHAR(36) DEFAULT NULL,
                UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
                INDEX IDX_1483A5E9F8C1AB04 (currently_active_organizations_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            CREATE TABLE users_organizations (
                users_id CHAR(36) NOT NULL,
                organizations_id CHAR(36) NOT NULL,
                INDEX IDX_4B99147267B3B43D (users_id),
                INDEX IDX_4B99147286288A55 (organizations_id),
                PRIMARY KEY (users_id, organizations_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            CREATE TABLE organization_groups (
                id CHAR(36) NOT NULL,
                name VARCHAR(256) NOT NULL,
                created_at DATE NOT NULL,
                access_rights TEXT DEFAULT NULL,
                is_default_for_new_members TINYINT NOT NULL,
                organizations_id CHAR(36) NOT NULL,
                INDEX IDX_F5E3E98586288A55 (organizations_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            CREATE TABLE users_organization_groups (
                users_id CHAR(36) NOT NULL,
                organization_groups_id CHAR(36) NOT NULL,
                INDEX IDX_977D3B1F67B3B43D (users_id),
                INDEX IDX_977D3B1F3700E7C9 (organization_groups_id),
                PRIMARY KEY (users_id, organization_groups_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            CREATE TABLE organization_invitations (
                id CHAR(36) NOT NULL,
                email VARCHAR(256) NOT NULL,
                created_at DATE NOT NULL,
                organizations_id CHAR(36) NOT NULL,
                INDEX IDX_137BB4D586288A55 (organizations_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            CREATE TABLE organizations (
                id CHAR(36) NOT NULL,
                name VARCHAR(256) DEFAULT NULL,
                owning_users_id CHAR(36) NOT NULL,
                INDEX IDX_427C1C7F91CA5BAF (owning_users_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            ALTER TABLE users
            ADD CONSTRAINT FK_1483A5E9F8C1AB04
            FOREIGN KEY (currently_active_organizations_id) REFERENCES organizations (id) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE users_organizations
            ADD CONSTRAINT FK_4B99147267B3B43D
            FOREIGN KEY (users_id) REFERENCES users (id)
        ');

        $this->addSql('
            ALTER TABLE users_organizations
            ADD CONSTRAINT FK_4B99147286288A55
            FOREIGN KEY (organizations_id) REFERENCES organizations (id)
        ');

        $this->addSql('
            ALTER TABLE organization_groups
            ADD CONSTRAINT FK_F5E3E98586288A55
            FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE users_organization_groups
            ADD CONSTRAINT FK_977D3B1F67B3B43D
            FOREIGN KEY (users_id) REFERENCES organization_groups (id)
        ');

        $this->addSql('
            ALTER TABLE users_organization_groups
            ADD CONSTRAINT FK_977D3B1F3700E7C9
            FOREIGN KEY (organization_groups_id) REFERENCES users (id)
        ');

        $this->addSql('
            ALTER TABLE organization_invitations
            ADD CONSTRAINT FK_137BB4D586288A55
            FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE
        ');

        $this->addSql('
            ALTER TABLE organizations
            ADD CONSTRAINT FK_427C1C7F91CA5BAF
            FOREIGN KEY (owning_users_id) REFERENCES users (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F8C1AB04');
        $this->addSql('ALTER TABLE users_organizations DROP FOREIGN KEY FK_4B99147267B3B43D');
        $this->addSql('ALTER TABLE users_organizations DROP FOREIGN KEY FK_4B99147286288A55');
        $this->addSql('ALTER TABLE organization_groups DROP FOREIGN KEY FK_F5E3E98586288A55');
        $this->addSql('ALTER TABLE users_organization_groups DROP FOREIGN KEY FK_977D3B1F67B3B43D');
        $this->addSql('ALTER TABLE users_organization_groups DROP FOREIGN KEY FK_977D3B1F3700E7C9');
        $this->addSql('ALTER TABLE organization_invitations DROP FOREIGN KEY FK_137BB4D586288A55');
        $this->addSql('ALTER TABLE organizations DROP FOREIGN KEY FK_427C1C7F91CA5BAF');

        $this->addSql('DROP TABLE users_organizations');
        $this->addSql('DROP TABLE users_organization_groups');
        $this->addSql('DROP TABLE organization_invitations');
        $this->addSql('DROP TABLE organization_groups');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE organizations');
    }
}
