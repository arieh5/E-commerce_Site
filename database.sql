-- Création de la base de données
CREATE DATABASE IF NOT EXISTS mea_tennis;
USE mea_tennis;

-- Suppression des tables existantes
DROP TABLE IF EXISTS Joueur_Match;
DROP TABLE IF EXISTS Commande_Billet;
DROP TABLE IF EXISTS Commande;
DROP TABLE IF EXISTS Billet;
DROP TABLE IF EXISTS Matchs;
DROP TABLE IF EXISTS Joueurs;
DROP TABLE IF EXISTS Utilisateur;

-- Table Joueurs
CREATE TABLE Joueurs (
    id_joueur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    nationalite VARCHAR(50) NOT NULL,
    rang_atp INT
);

-- Table Match
CREATE TABLE Matchs (
    id_match INT AUTO_INCREMENT PRIMARY KEY,
    heure TIME NOT NULL,
    nom_court VARCHAR(50) NOT NULL,
    type_de_match ENUM('Simple', 'Double') NOT NULL,
    place_dans_le_tournoi VARCHAR(50) NOT NULL
);

-- Table Utilisateur
CREATE TABLE Utilisateur (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion TIMESTAMP,
    newsletter BOOLEAN DEFAULT TRUE
);

-- Table Billet
CREATE TABLE Billet (
    id_billet INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    categorie VARCHAR(50) NOT NULL,
    vendu BOOLEAN DEFAULT 0,
    id_match INT,
    FOREIGN KEY (id_match) REFERENCES Matchs(id_match)
);

-- Table Commande
CREATE TABLE Commande (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    montant_total DECIMAL(10,2) NOT NULL,
    frais_service DECIMAL(10,2) NOT NULL,
    mode_paiement VARCHAR(50),
    statut_paiement ENUM('en_attente', 'complete', 'annule', 'rembourse') DEFAULT 'en_attente',
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_paiement TIMESTAMP NULL,
    reference_transaction VARCHAR(100),
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur)
);

-- Table Commande_Billet
CREATE TABLE Commande_Billet (
    id_commande INT,
    id_billet INT,
    PRIMARY KEY (id_commande, id_billet),
    FOREIGN KEY (id_commande) REFERENCES Commande(id_commande),
    FOREIGN KEY (id_billet) REFERENCES Billet(id_billet)
);

-- Table de liaison Joueur_Match
CREATE TABLE Joueur_Match (
    id_joueur INT,
    id_match INT,
    PRIMARY KEY (id_joueur, id_match),
    FOREIGN KEY (id_joueur) REFERENCES Joueurs(id_joueur),
    FOREIGN KEY (id_match) REFERENCES Matchs(id_match)
);

-- Insertion des données de test pour les joueurs
INSERT INTO Joueurs (nom, prenom, age, nationalite, rang_atp) VALUES 
('Djokovic', 'Novak', 36, 'Serbie', 1),
('Alcaraz', 'Carlos', 20, 'Espagne', 2),
('Medvedev', 'Daniil', 28, 'Russie', 3),
('Sinner', 'Jannik', 22, 'Italie', 4);

-- Insertion des données de test pour les matchs
INSERT INTO Matchs (heure, nom_court, type_de_match, place_dans_le_tournoi) VALUES 
('14:00:00', 'Court Philippe-Chatrier', 'Simple', 'Quart de finale'),
('16:00:00', 'Court Suzanne-Lenglen', 'Simple', 'Demi-finale'),
('18:00:00', 'Court Philippe-Chatrier', 'Simple', 'Finale');

-- Insertion des données de test pour les utilisateurs
INSERT INTO Utilisateur (nom, email, mot_de_passe, telephone, newsletter) VALUES 
('Dupont Alice', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+33612345678', TRUE), -- mot de passe: password
('Martin Bob', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+33687654321', TRUE); -- mot de passe: password

-- Insertion des données de test pour les billets
INSERT INTO Billet (date, prix, categorie, id_match) VALUES 
('2025-05-26', 150.00, 'Catégorie 1', 1),
('2025-05-26', 250.00, 'Carré Or', 1),
('2025-05-26', 90.00, 'Catégorie 2', 1),
('2025-05-27', 200.00, 'Catégorie 1', 2),
('2025-05-27', 350.00, 'Carré Or', 2),
('2025-05-27', 120.00, 'Catégorie 2', 2),
('2025-05-28', 300.00, 'Catégorie 1', 3),
('2025-05-28', 500.00, 'Carré Or', 3),
('2025-05-28', 180.00, 'Catégorie 2', 3);

-- Insertion des données de test pour les relations joueur-match
INSERT INTO Joueur_Match (id_joueur, id_match) VALUES 
(1, 1), -- Djokovic - Quart de finale
(2, 1), -- Alcaraz - Quart de finale
(1, 2), -- Djokovic - Demi-finale
(3, 2), -- Medvedev - Demi-finale
(1, 3), -- Djokovic - Finale
(3, 3); -- Medvedev - Finale
