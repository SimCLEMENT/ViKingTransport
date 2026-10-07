# SAÉ 2.04 – 2.05 – 2.06 : VikingTransport

Application Web de gestion de réservations de billets de bus pour un réseau fictif de cars normands.

🔗 **Voir le projet en ligne :** https://simon-server.tail47e954.ts.net:8443/

🎥 **Démo vidéo :** [lien YouTube non répertorié]

## 📖 Contexte

VikingTransport est un **réseau fictif de cars normands** qui assure les transports régionaux non urbains en Normandie. L'objectif de la SAÉ est de développer une **application Web de gestion de réservations de billets de bus** pour cette société, en conditions proches d'un vrai projet professionnel (client, méthode agile, travail d'équipe).

Réalisé dans le cadre du BUT Informatique — IUT Grand Ouest Normandie (Caen), année universitaire 2025-2026, semestre 2.

Le projet combine trois compétences :

| Code | Compétence |
|------|------------|
| **S2.04** | Exploitation d'une base de données (conception, sécurité, exploitation) |
| **S2.05** | Gestion d'un projet (cahier des charges, suivi, backlog) |
| **S2.06** | Organisation d'un travail d'équipe (rôles, agilité, communication) |

## 🏢 Le client : société VikingTransport

- 4 associés fictifs : E. Alaphilippe, S. Lebrave, C. Pasamsung, S. Supormoi
- Le client joue le rôle de **Product Owner** : il commandite le produit, donne son avis, fixe les priorités, mais **n'aide pas techniquement** l'équipe.
- Réseau desservant **19 lignes** à travers toute la Normandie (Caen, Rouen, Cherbourg, Le Havre, Alençon, etc.).

## ✨ Fonctionnalités

**Client non inscrit**
- Visualiser les lignes et horaires
- Acheter un billet sur la plateforme
- Créer un compte

**Client inscrit (fidélisé)**
- Se connecter à l'application
- Rechercher des voyages/trajets (coût, durée, correspondances)
- Réserver un voyage (une ligne, une partie de ligne, ou plusieurs lignes)
- Gagner et utiliser des points de fidélité
- Modifier ses informations, consulter l'historique de ses voyages et de ses points

**Administrateur**
- Gérer les comptes clients (liste, modification, suppression, inactifs)
- Visualiser toutes les réservations
- Consulter des statistiques (lignes/trajets les + ou - vendus, clients fidèles)
- Réaliser des campagnes de promotion
- Modifier lignes et horaires

## 🚀 Déploiement

Le projet a été conçu pour une base de données **Oracle** (serveur de l'IUT), non accessible en dehors du réseau universitaire. Pour le rendre consultable en ligne, il a été auto-hébergé sur un mini-serveur personnel, avec les adaptations suivantes :

- **Base de données** : migration du schéma et des données d'Oracle vers **MariaDB** (conversion des types, des dates, et des fonctions spécifiques Oracle — `SYSDATE`, `TO_DATE`, `TO_CHAR`, `ROWNUM`, etc. — vers leurs équivalents MySQL)
- **Conteneurisation** : application déployée via **Docker**
- **Sécurité** : les identifiants de connexion à la base ont été sortis du code source et externalisés dans des variables d'environnement (fichier `.env`, non versionné)
- **Accès en ligne** : exposition sécurisée via **Tailscale Funnel** (tunnel HTTPS), sans ouverture de port sur la box internet

## 🛠️ Langages et technologies utilisés

- HTML / CSS / PHP / JS
- Serveur Web de l'université
- Base de données Oracle (SQL Developer)

## 🔁 Méthode agile utilisée

- **5 itérations (sprints)** réparties sur **3 jours** (24h de projet tutoré)
- Pratiques mises en œuvre :
  - **Itération 0** : mise en place équipe + environnement (Git...), objectif minimal = 1 fonctionnalité montrable
  - **Tableau des tâches** (Backlog / À faire / En cours / Terminée), à tenir à jour, éviter trop de tâches "en cours"
  - **Recette** : démo au client, qui vérifie les fonctionnalités promises et les retours précédents
  - **Rétrospective** après chaque recette (tableau Bien / Moins bien / Question à creuser / Qu'en tirer ?)
  - **Stand-up meeting** quotidien (15 min max, debout, 3 questions : hier / aujourd'hui / blocages)

## ✍️ Auteurs

- CLEMENT Simon
- LANGLOIS Kylian
- MUNOZ Paul
- NAVARRO Pierre
- OUASRI Hamza
- TOULORGE Nathan
- WALLAERT-BEAUGENDRE Noor
