# SAÉ 2.04 – 2.05 – 2.06 : VikingTransport

**BUT Informatique – IUT Grand Ouest Normandie (Caen)**
**Année universitaire 2025-2026 – Semestre 2**

---

## 🎯 Présentation du projet

VikingTransport est un **réseau fictif de cars normands** qui assure les transports régionaux non urbains en Normandie. L'objectif de la SAÉ est de développer une **application Web de gestion de réservations de billets de bus** pour cette société, en conditions proches d'un vrai projet professionnel (client, méthode agile, travail d'équipe).

Le projet combine trois compétences :

| Code | Compétence |
|------|------------|
| **S2.04** | Exploitation d'une base de données (conception, sécurité, exploitation) |
| **S2.05** | Gestion d'un projet (cahier des charges, suivi, backlog) |
| **S2.06** | Organisation d'un travail d'équipe (rôles, agilité, communication) |

---

## 🏢 Le client : société VikingTransport

- 4 associés fictifs : E. Alaphilippe, S. Lebrave, C. Pasamsung, S. Supormoi
- Le client joue le rôle de **Product Owner** : il commandite le produit, donne son avis, fixe les priorités, mais **n'aide pas techniquement** l'équipe.
- Réseau desservant **19 lignes** à travers toute la Normandie (Caen, Rouen, Cherbourg, Le Havre, Alençon, etc.).

---

## 🧩 Fonctionnalités attendues

### Client non inscrit
- Visualiser les lignes et horaires
- Acheter un billet sur la plateforme
- Créer un compte

### Client inscrit (fidélisé)
- Se connecter à l'application
- Rechercher des voyages/trajets (coût, durée, correspondances)
- Réserver un voyage (une ligne, une partie de ligne, ou plusieurs lignes)
- Gagner et utiliser des points de fidélité
- Modifier ses informations, consulter l'historique de ses voyages et de ses points

### Administrateur
- Gérer les comptes clients (liste, modification, suppression, inactifs)
- Visualiser toutes les réservations
- Consulter des statistiques (lignes/trajets les + ou - vendus, clients fidèles)
- Réaliser des campagnes de promotion
- Modifier lignes et horaires

👉 Les fonctionnalités sont **priorisées** (backlog fourni), il faut les développer dans l'ordre de priorité, pas forcément dans l'ordre "logique".

---

## 🛠️ Technologies imposées

- **HTML / CSS / PHP / JS**
- Serveur Web de l'université
- Base de données Oracle (SQL Developer)

---

## 🔁 Méthode agile utilisée

- **5 itérations (sprints)** réparties sur **3 jours** (24h de projet tutoré)
- Pratiques mises en œuvre :
  - **Itération 0** : mise en place équipe + environnement (Git...), objectif minimal = 1 fonctionnalité montrable
  - **Tableau des tâches** (Backlog / À faire / En cours / Terminée), à tenir à jour, éviter trop de tâches "en cours"
  - **Recette** : démo au client, qui vérifie les fonctionnalités promises et les retours précédents
  - **Rétrospective** après chaque recette (tableau Bien / Moins bien / Question à creuser / Qu'en tirer ?)
  - **Stand-up meeting** quotidien (15 min max, debout, 3 questions : hier / aujourd'hui / blocages)

