<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour restructurer les tables d'utilisateurs - chaque type d'utilisateur aura sa propre table complète
 */
final class Version20250120000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restructure user tables to be standalone with all fields instead of inheritance';
    }

    public function up(Schema $schema): void
    {
        // Sauvegarder les données existantes
        $this->addSql('CREATE TEMPORARY TABLE temp_users AS SELECT * FROM user');
        $this->addSql('CREATE TEMPORARY TABLE temp_administrators AS SELECT * FROM administrator');
        $this->addSql('CREATE TEMPORARY TABLE temp_parent_users AS SELECT * FROM parent_user');
        $this->addSql('CREATE TEMPORARY TABLE temp_teachers AS SELECT * FROM teacher');
        $this->addSql('CREATE TEMPORARY TABLE temp_students AS SELECT * FROM student');

        // Supprimer les contraintes de clés étrangères existantes
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33BF396750');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33727ACA70');
        $this->addSql('ALTER TABLE teacher DROP FOREIGN KEY FK_B0F6A6D5BF396750');
        $this->addSql('ALTER TABLE parent_user DROP FOREIGN KEY FK_B070E0FDBF396750');
        $this->addSql('ALTER TABLE administrator DROP FOREIGN KEY FK_B0F6A6D5BF396750');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F79E92E8C');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA4F84F6E');
        $this->addSql('ALTER TABLE conversation_user DROP FOREIGN KEY FK_5AECB555A76ED395');

        // Supprimer les tables existantes
        $this->addSql('DROP TABLE administrator');
        $this->addSql('DROP TABLE parent_user');
        $this->addSql('DROP TABLE teacher');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE conversation_user');

        // Recréer les tables d'utilisateurs autonomes avec tous les champs

        // Table Administrator autonome
        $this->addSql('CREATE TABLE administrator (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            firstname VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            privileges LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\',
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table ParentUser autonome
        $this->addSql('CREATE TABLE parent_user (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            firstname VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            profession VARCHAR(255) DEFAULT NULL,
            telephone VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table Teacher autonome
        $this->addSql('CREATE TABLE teacher (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            firstname VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            specialite VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table Student autonome
        $this->addSql('CREATE TABLE student (
            id INT AUTO_INCREMENT NOT NULL,
            classe_id INT DEFAULT NULL,
            parent_id INT DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            firstname VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            num_student VARCHAR(255) NOT NULL,
            date_naissance DATE NOT NULL,
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            INDEX IDX_B723AF338F5EA509 (classe_id),
            INDEX IDX_B723AF33727ACA70 (parent_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Modifier les tables Message et Notification pour relations polymorphes
        $this->addSql('ALTER TABLE message DROP COLUMN emetteur_id');
        $this->addSql('ALTER TABLE message ADD emetteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD emetteur_type VARCHAR(50) DEFAULT NULL');

        $this->addSql('ALTER TABLE notification DROP COLUMN destinataire_id');
        $this->addSql('ALTER TABLE notification ADD destinataire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD destinataire_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD titre VARCHAR(255) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE notification ADD priorite VARCHAR(20) NOT NULL DEFAULT "normale"');
        $this->addSql('ALTER TABLE notification ADD lu TINYINT(1) NOT NULL DEFAULT 0');

        // Créer la nouvelle table pour les participants de conversation
        $this->addSql('CREATE TABLE conversation_participant (
            id INT AUTO_INCREMENT NOT NULL,
            conversation_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            user_type VARCHAR(50) DEFAULT NULL,
            joined_at DATETIME NOT NULL,
            INDEX IDX_5AECB5559AC0396 (conversation_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Mettre à jour la table conversation
        $this->addSql('ALTER TABLE conversation ADD titre VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation ADD date_creation DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation ADD active TINYINT(1) NOT NULL DEFAULT 1');

        // Ajouter les contraintes de clés étrangères
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33727ACA70 FOREIGN KEY (parent_id) REFERENCES parent_user (id)');
        $this->addSql('ALTER TABLE conversation_participant ADD CONSTRAINT FK_5AECB5559AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');

        // Migrer les données existantes vers les nouvelles tables
        $this->addSql('INSERT INTO administrator (id, email, roles, password, firstname, lastname, privileges)
            SELECT u.id, u.email, u.roles, u.password, u.firstname, u.lastname, a.privileges
            FROM temp_users u
            JOIN temp_administrators a ON u.id = a.id
            WHERE u.role = "admin"');

        $this->addSql('INSERT INTO parent_user (id, email, roles, password, firstname, lastname, profession, telephone)
            SELECT u.id, u.email, u.roles, u.password, u.firstname, u.lastname, p.profession, p.telephone
            FROM temp_users u
            JOIN temp_parent_users p ON u.id = p.id
            WHERE u.role = "parent"');

        $this->addSql('INSERT INTO teacher (id, email, roles, password, firstname, lastname, specialite)
            SELECT u.id, u.email, u.roles, u.password, u.firstname, u.lastname, t.specialite
            FROM temp_users u
            JOIN temp_teachers t ON u.id = t.id
            WHERE u.role = "teacher"');

        $this->addSql('INSERT INTO student (id, classe_id, parent_id, email, roles, password, firstname, lastname, num_student, date_naissance)
            SELECT u.id, s.classe_id, s.parent_id, u.email, u.roles, u.password, u.firstname, u.lastname, s.num_etudiant, s.date_naissance
            FROM temp_users u
            JOIN temp_students s ON u.id = s.id
            WHERE u.role = "student"');

        // Supprimer la table user originale
        $this->addSql('DROP TABLE user');

        // Supprimer les tables temporaires
        $this->addSql('DROP TEMPORARY TABLE temp_users');
        $this->addSql('DROP TEMPORARY TABLE temp_administrators');
        $this->addSql('DROP TEMPORARY TABLE temp_parent_users');
        $this->addSql('DROP TEMPORARY TABLE temp_teachers');
        $this->addSql('DROP TEMPORARY TABLE temp_students');
    }

    public function down(Schema $schema): void
    {
        // Cette migration est irréversible car elle change fondamentalement la structure
        $this->throwIrreversibleMigrationException();
    }
}