<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201104137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE account_cores (
              id CHAR(36) NOT NULL,
              created_at DATETIME NOT NULL,
              currently_active_organization_id CHAR(36) DEFAULT NULL,
              email VARCHAR(1024) NOT NULL,
              roles JSON NOT NULL,
              password_hash VARCHAR(1024) NOT NULL,
              UNIQUE INDEX UNIQ_CB89C7FEE7927C74 (email),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE app_notifications (
              id CHAR(36) NOT NULL,
              created_at DATETIME NOT NULL,
              message VARCHAR(1024) NOT NULL,
              url VARCHAR(1024) NOT NULL,
              type SMALLINT UNSIGNED NOT NULL,
              is_read TINYINT NOT NULL,
              INDEX created_at_is_read_idx (created_at, is_read),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE etfs_shared_bundle_command_run_summaries (
              id CHAR(36) NOT NULL,
              command_name VARCHAR(512) NOT NULL,
              arguments VARCHAR(1024) NOT NULL,
              options VARCHAR(1024) NOT NULL,
              hostname VARCHAR(1024) NOT NULL,
              envvars VARCHAR(8192) NOT NULL,
              started_at DATETIME NOT NULL,
              finished_at DATETIME DEFAULT NULL,
              finished_due_to_no_initial_lock TINYINT NOT NULL,
              finished_due_to_got_behind_lock TINYINT NOT NULL,
              finished_due_to_failed_to_update_lock TINYINT NOT NULL,
              finished_due_to_rollout_signal TINYINT NOT NULL,
              finished_normally TINYINT NOT NULL,
              number_of_handled_elements INT NOT NULL,
              max_allocated_memory INT NOT NULL,
              INDEX command_name_started_at_idx (command_name, started_at),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE etfs_shared_bundle_signals (
              name VARCHAR(64) NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (name)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organization_groups (
              id CHAR(36) NOT NULL,
              name VARCHAR(256) NOT NULL,
              created_at DATE NOT NULL,
              access_rights TEXT NOT NULL,
              is_default_for_new_members TINYINT NOT NULL,
              organizations_id CHAR(36) NOT NULL,
              INDEX IDX_F5E3E98586288A55 (organizations_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organization_invitations (
              id CHAR(36) NOT NULL,
              email VARCHAR(256) NOT NULL,
              created_at DATE NOT NULL,
              organizations_id CHAR(36) NOT NULL,
              INDEX IDX_137BB4D586288A55 (organizations_id),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE organizations (
              id CHAR(36) NOT NULL,
              owning_users_id CHAR(36) NOT NULL,
              name VARCHAR(256) DEFAULT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
              id BIGINT AUTO_INCREMENT NOT NULL,
              body LONGTEXT NOT NULL,
              headers LONGTEXT NOT NULL,
              queue_name VARCHAR(190) NOT NULL,
              created_at DATETIME NOT NULL,
              available_at DATETIME NOT NULL,
              delivered_at DATETIME DEFAULT NULL,
              INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (
                queue_name, available_at, delivered_at,
                id
              ),
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sessions (
              sess_id VARBINARY(128) NOT NULL,
              sess_data LONGBLOB NOT NULL,
              sess_lifetime INT UNSIGNED NOT NULL,
              sess_time INT UNSIGNED NOT NULL,
              INDEX sess_lifetime_idx (sess_lifetime),
              PRIMARY KEY (sess_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lock_keys (
              key_id VARCHAR(64) NOT NULL,
              key_token VARCHAR(44) NOT NULL,
              key_expiration INT UNSIGNED NOT NULL,
              PRIMARY KEY (key_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              organization_groups
            ADD
              CONSTRAINT FK_F5E3E98586288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              organization_invitations
            ADD
              CONSTRAINT FK_137BB4D586288A55 FOREIGN KEY (organizations_id) REFERENCES organizations (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_groups DROP FOREIGN KEY FK_F5E3E98586288A55');
        $this->addSql('ALTER TABLE organization_invitations DROP FOREIGN KEY FK_137BB4D586288A55');
        $this->addSql('DROP TABLE account_cores');
        $this->addSql('DROP TABLE app_notifications');
        $this->addSql('DROP TABLE etfs_shared_bundle_command_run_summaries');
        $this->addSql('DROP TABLE etfs_shared_bundle_signals');
        $this->addSql('DROP TABLE organization_groups');
        $this->addSql('DROP TABLE organization_invitations');
        $this->addSql('DROP TABLE organizations');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE lock_keys');
    }
}
