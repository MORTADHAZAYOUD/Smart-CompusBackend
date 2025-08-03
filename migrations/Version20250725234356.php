<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725234356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE administrator (id INT AUTO_INCREMENT NOT NULL, privileges LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation_user (conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5AECB5559AC0396 (conversation_id), INDEX IDX_5AECB555A76ED395 (user_id), PRIMARY KEY(conversation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, date DATETIME NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE matiere (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT DEFAULT NULL, emetteur_id INT DEFAULT NULL, contenu LONGTEXT NOT NULL, date DATETIME NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307F79E92E8C (emetteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, student_id INT DEFAULT NULL, seance_id INT DEFAULT NULL, valeur DOUBLE PRECISION NOT NULL, INDEX IDX_CFBDFA14CB944F1A (student_id), INDEX IDX_CFBDFA14E3797A94 (seance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, destinataire_id INT DEFAULT NULL, contenu VARCHAR(255) NOT NULL, vue TINYINT(1) NOT NULL, date DATETIME NOT NULL, INDEX IDX_BF5476CAA4F84F6E (destinataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parent_user (id INT NOT NULL, profession VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presence (id INT AUTO_INCREMENT NOT NULL, student_id INT DEFAULT NULL, seance_id INT DEFAULT NULL, present TINYINT(1) NOT NULL, INDEX IDX_6977C7A5CB944F1A (student_id), INDEX IDX_6977C7A5E3797A94 (seance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, classe_id INT DEFAULT NULL, enseignant_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, date DATETIME NOT NULL, presentiel TINYINT(1) NOT NULL, visio_link VARCHAR(255) DEFAULT NULL, INDEX IDX_DF7DFD0E8F5EA509 (classe_id), INDEX IDX_DF7DFD0EE455FCC0 (enseignant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statistique (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, valeur DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student (id INT NOT NULL, classe_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, num_etudiant VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, INDEX IDX_B723AF338F5EA509 (classe_id), INDEX IDX_B723AF33727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE teacher (id INT NOT NULL, specialite VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB5559AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB555A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F79E92E8C FOREIGN KEY (emetteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE parent_user ADD CONSTRAINT FK_B070E0FDBF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A5CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A5E3797A94 FOREIGN KEY (seance_id) REFERENCES seance (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0EE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES teacher (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF338F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33727ACA70 FOREIGN KEY (parent_id) REFERENCES parent_user (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33BF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE teacher ADD CONSTRAINT FK_B0F6A6D5BF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD role VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation_user DROP FOREIGN KEY FK_5AECB5559AC0396');
        $this->addSql('ALTER TABLE conversation_user DROP FOREIGN KEY FK_5AECB555A76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F79E92E8C');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14CB944F1A');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14E3797A94');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA4F84F6E');
        $this->addSql('ALTER TABLE parent_user DROP FOREIGN KEY FK_B070E0FDBF396750');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A5CB944F1A');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A5E3797A94');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E8F5EA509');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0EE455FCC0');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF338F5EA509');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33727ACA70');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33BF396750');
        $this->addSql('ALTER TABLE teacher DROP FOREIGN KEY FK_B0F6A6D5BF396750');
        $this->addSql('DROP TABLE administrator');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE conversation_user');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE matiere');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE parent_user');
        $this->addSql('DROP TABLE presence');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE statistique');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE teacher');
        $this->addSql('ALTER TABLE user DROP role');
    }
}
