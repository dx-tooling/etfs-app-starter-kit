<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125190646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Drop is_verified column from users (no longer in entity)
        $this->addSql('ALTER TABLE users DROP COLUMN is_verified');

        // 2. Drop FK constraint: users.currently_active_organizations_id -> organizations
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F8C1AB04');

        // 3. Drop FK constraint: organizations.owning_users_id -> users
        $this->addSql('ALTER TABLE organizations DROP FOREIGN KEY FK_427C1C7F91CA5BAF');

        // 4. Drop FK constraints on users_organizations join table
        $this->addSql('ALTER TABLE users_organizations DROP FOREIGN KEY FK_4B99147267B3B43D');
        $this->addSql('ALTER TABLE users_organizations DROP FOREIGN KEY FK_4B99147286288A55');

        // 5. Drop FK constraints on users_organization_groups join table
        $this->addSql('ALTER TABLE users_organization_groups DROP FOREIGN KEY FK_977D3B1F67B3B43D');
        $this->addSql('ALTER TABLE users_organization_groups DROP FOREIGN KEY FK_977D3B1F3700E7C9');
    }

    public function down(Schema $schema): void
    {
        // 1. Re-add is_verified column
        $this->addSql('ALTER TABLE users ADD is_verified TINYINT NOT NULL DEFAULT 0');

        // 2. Re-add FK constraint: users.currently_active_organizations_id -> organizations
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F8C1AB04 FOREIGN KEY (currently_active_organizations_id) REFERENCES organizations (id) ON DELETE CASCADE');

        // 3. Re-add FK constraint: organizations.owning_users_id -> users
        $this->addSql('ALTER TABLE organizations ADD CONSTRAINT FK_427C1C7F91CA5BAF FOREIGN KEY (owning_users_id) REFERENCES users (id) ON DELETE CASCADE');

        // 4. Re-add FK constraints on users_organizations join table
        $this->addSql('ALTER TABLE users_organizations ADD CONSTRAINT FK_4B99147267B3B43D FOREIGN KEY (users_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users_organizations ADD CONSTRAINT FK_4B99147286288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id)');

        // 5. Re-add FK constraints on users_organization_groups join table
        $this->addSql('ALTER TABLE users_organization_groups ADD CONSTRAINT FK_977D3B1F67B3B43D FOREIGN KEY (users_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users_organization_groups ADD CONSTRAINT FK_977D3B1F3700E7C9 FOREIGN KEY (organization_groups_id) REFERENCES organization_groups (id)');
    }
}
