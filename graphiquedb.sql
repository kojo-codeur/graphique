-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le :  ven. 19 sep. 2025 à 15:25
-- Version du serveur :  10.1.28-MariaDB
-- Version de PHP :  7.1.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `graphiquedb`
--

-- --------------------------------------------------------

--
-- Structure de la table `graphique`
--

CREATE TABLE `graphique` (
  `id` int(11) NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `valeur` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `graphique`
--

INSERT INTO `graphique` (`id`, `categorie`, `valeur`, `created_at`) VALUES
(1, 'Ventes', 150, '2025-09-19 12:38:45'),
(2, 'Ventes', 200, '2025-09-19 12:38:45'),
(3, 'Utilisateurs', 50, '2025-09-19 12:38:45'),
(4, 'Utilisateurs', 65, '2025-09-19 12:38:45'),
(5, 'Visites', 300, '2025-09-19 12:38:45'),
(6, 'Visites', 350, '2025-09-19 12:38:45'),
(7, 'production', 120, '2025-09-19 12:38:45'),
(8, 'achats', 180, '2025-09-19 12:38:45'),
(9, 'emplois', 90, '2025-09-19 12:38:45'),
(10, 'Utilisateurs', 300, '2025-09-19 12:56:42'),
(11, 'Visites', 600, '2025-09-19 12:57:04');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `graphique`
--
ALTER TABLE `graphique`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `graphique`
--
ALTER TABLE `graphique`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
