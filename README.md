# SmartCampus - ERP Scolaire

SmartCampus est une solution ERP scolaire moderne développée avec **Symfony 7.3** (backend) et **Angular 19** (frontend). Cette plateforme permet aux établissements scolaires de gérer efficacement leurs utilisateurs, séances, présences, notes, communication et bien plus encore.

## 🏗️ Architecture

### Backend (Symfony 7.3)
- **Framework** : Symfony 7.3 avec PHP 8.2+
- **Base de données** : MySQL
- **Authentification** : JWT (LexikJWTAuthenticationBundle)
- **API** : REST API avec documentation Swagger/OpenAPI
- **Architecture** : MVC avec Repository Pattern

### Frontend (Angular 19)
- **Framework** : Angular 19
- **UI Library** : Angular Material + Bootstrap 5
- **Authentification** : JWT avec intercepteurs HTTP
- **Architecture** : Modulaire avec lazy loading

## 📋 Fonctionnalités

### 🔐 Système d'authentification
- Connexion sécurisée avec JWT
- Gestion des rôles (Admin, Enseignant, Étudiant, Parent)
- Guards de protection des routes

### 👥 Gestion des utilisateurs (SmartProfile)
- CRUD complet des utilisateurs
- Filtrage par rôle et classe
- Gestion des relations parent-enfant
- Assignation aux classes

### 🏫 Gestion des classes
- Création et gestion des classes
- Assignation des enseignants
- Statistiques par classe
- Gestion de l'effectif

### 📚 SessionTracker
- Création et gestion des séances (cours, devoirs, examens)
- Mode présentiel/distanciel/hybride
- Liens de visioconférence
- Gestion des présences
- Attribution des notes

### 📅 SmartCalendar
- Emploi du temps hebdomadaire
- Vue filtrée par classe/enseignant/matière
- Événements spéciaux
- Synchronisation automatique

### 📧 ConnectRoom (Messagerie)
- Messagerie interne entre utilisateurs
- Conversations de groupe
- Historique des messages
- Notifications en temps réel

### 🔔 Système de notifications
- Notifications dans l'interface
- Notifications par email (Symfony Mailer)
- Différents niveaux de priorité
- Notifications automatiques (absences, nouvelles notes, etc.)

### 📊 Tableau de bord
- Statistiques générales pour les administrateurs
- Graphiques des données
- Métriques de performance
- Vues personnalisées par rôle

## 🚀 Installation

### Prérequis
- PHP 8.2+
- Node.js 18+
- MySQL 8.0+
- Composer
- npm ou yarn

### Backend (Symfony)

1. **Installer les dépendances PHP**
```bash
cd /workspace
composer install
```

2. **Configuration de la base de données**
```bash
# Créer le fichier .env.local
echo "DATABASE_URL=\"mysql://username:password@localhost:3306/smartcampus_db?serverVersion=8.0&charset=utf8mb4\"" > .env.local
```

3. **Créer la base de données et les migrations**
```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

4. **Configurer JWT**
```bash
php bin/console lexik:jwt:generate-keypair
```

5. **Démarrer le serveur Symfony**
```bash
php bin/console server:run localhost:8000
# ou
symfony serve -d
```

### Frontend (Angular)

1. **Installer les dépendances Node.js**
```bash
cd smartcampus-frontend
npm install
```

2. **Démarrer le serveur de développement**
```bash
npm start
# ou
ng serve
```

L'application sera accessible sur `http://localhost:4200`

## 🎯 Utilisation

### Comptes par défaut
Après installation, vous pouvez créer des comptes via l'API ou directement en base de données :

- **Administrateur** : admin@smartcampus.fr
- **Enseignant** : teacher@smartcampus.fr  
- **Étudiant** : student@smartcampus.fr
- **Parent** : parent@smartcampus.fr

### Cas d'utilisation selon les diagrammes

#### Pour les Étudiants
- Consulter son profil et ses informations
- Voir ses notes et présences
- Accéder au planning des cours
- Recevoir et envoyer des messages
- Consulter les notifications

#### Pour les Parents
- Suivre le profil de ses enfants
- Consulter les présences et notes
- Recevoir des notifications d'absence
- Communiquer avec les enseignants
- Accéder au planning de ses enfants

#### Pour les Enseignants
- Gérer ses séances (création, modification)
- Marquer les présences des étudiants
- Attribuer des notes
- Consulter les profils des élèves de ses classes
- Planifier des activités
- Analyser les résultats

#### Pour les Administrateurs
- Gestion complète des utilisateurs
- Création et configuration des classes
- Gestion des emplois du temps
- Visualisation des statistiques globales
- Configuration du système
- Gestion des paramètres

## 🛠️ API Endpoints

### Authentification
- `POST /api/register` - Inscription
- `POST /api/login_check` - Connexion
- `GET /api/profile` - Profil utilisateur
- `PUT /api/profile` - Mise à jour du profil

### Utilisateurs
- `GET /api/users` - Liste des utilisateurs
- `GET /api/users/students` - Liste des étudiants
- `GET /api/users/teachers` - Liste des enseignants
- `GET /api/users/parents` - Liste des parents
- `POST /api/users` - Créer un utilisateur
- `PUT /api/users/{id}` - Modifier un utilisateur
- `DELETE /api/users/{id}` - Supprimer un utilisateur

### Classes
- `GET /api/classes` - Liste des classes
- `POST /api/classes` - Créer une classe
- `PUT /api/classes/{id}` - Modifier une classe
- `DELETE /api/classes/{id}` - Supprimer une classe
- `GET /api/classes/{id}/students` - Étudiants d'une classe

## 🗂️ Structure du projet

```
/workspace/
├── 📁 config/              # Configuration Symfony
├── 📁 src/
│   ├── 📁 Controller/       # Contrôleurs API
│   ├── 📁 Entity/          # Entités Doctrine
│   ├── 📁 Repository/      # Repositories
│   └── 📁 Service/         # Services métier
├── 📁 smartcampus-frontend/
│   ├── 📁 src/app/
│   │   ├── 📁 components/   # Composants Angular
│   │   ├── 📁 services/     # Services Angular
│   │   ├── 📁 models/       # Modèles TypeScript
│   │   ├── 📁 guards/       # Guards de routes
│   │   └── 📁 modules/      # Modules lazy-loaded
│   └── 📁 assets/          # Ressources statiques
└── 📁 public/              # Point d'entrée web Symfony
```

## 🔧 Technologies utilisées

### Backend
- **Symfony 7.3** - Framework PHP
- **Doctrine ORM** - Mapping objet-relationnel
- **LexikJWTAuthenticationBundle** - Authentification JWT
- **Symfony Mailer** - Envoi d'emails
- **NelmioApiDocBundle** - Documentation API
- **NelmioCorsBundle** - CORS

### Frontend
- **Angular 19** - Framework TypeScript
- **Angular Material** - Composants UI
- **Bootstrap 5** - Framework CSS
- **RxJS** - Programmation réactive
- **TypeScript** - Typage statique

## 📱 Responsive Design

L'application est entièrement responsive et s'adapte aux différentes tailles d'écran :
- **Desktop** : Interface complète avec sidebar
- **Tablet** : Interface adaptée
- **Mobile** : Interface optimisée pour mobile

## 🔒 Sécurité

- Authentification JWT sécurisée
- Contrôle d'accès basé sur les rôles (RBAC)
- Protection CSRF
- Validation des données côté client et serveur
- Hashage sécurisé des mots de passe

## 📈 Performance

- Lazy loading des modules Angular
- Optimisation des requêtes Doctrine
- Cache HTTP approprié
- Minification et optimisation des assets

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push sur la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 License

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👥 Support

Pour toute question ou support, veuillez créer une issue sur GitHub ou contacter l'équipe de développement.

---

**SmartCampus** - Votre solution ERP scolaire moderne et efficace 🎓