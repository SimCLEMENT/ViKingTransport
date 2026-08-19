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

## 🗄️ Base de données

- Un premier **MCD partiel** est fourni (`viking_etu.ws`), à compléter en TD (semaine du 30/03/2026).
- Entités clés : `Client`, `Commune`, `Département`, `Ligne`, `Réservation`, `Étape`, `Nœud`, `Tarif`, `Réduction`, `Niveau Client`.
- Points clés du modèle métier :
  - Un **nœud** ≠ une **étape** ≠ une **commune** (bien distinguer ces notions).
  - 1 point de fidélité gagné tous les 10 km (minimum 1 point, uniquement en entiers).
  - Un client perd ses points après 1 an d'inactivité, et son compte est supprimé après 2 ans.
- Travail en TD R2.06 (3h) : compléter le MCD, générer le MLD/script, insérer les départements, réaliser une série de requêtes SQL (20 requêtes + partie "durée du voyage le plus long").
- TP noté R2.06 (1h45) : insertion de données via Calc + interrogation/mise à jour via SQL Developer.

---

## 🛠️ Technologies imposées

- **HTML / CSS / PHP / JS**
- Serveur Web de l'université
- Base de données Oracle (SQL Developer)
- Usage de l'**IA générative** toléré mais encadré :
  - ✅ conseillé pour comprendre une erreur, un concept, faire du pair programming
  - ❌ déconseillé pour copier-coller du code non compris ou générer une fonctionnalité entière

---

## 👥 Organisation en équipe (projet 3 jours)

- **8 groupes** de 7-8 étudiants, répartis en 4 binômes de groupes + 4 coachs :

| Salle | Groupes | Coach |
|-------|---------|-------|
| 2127 | Groupe 5 + Groupe 6 | C. Passoni-Chevalier |
| 2235 | Groupe 1 + Groupe 2 | S. Secouard |
| 2237 | Groupe 3 + Groupe 4 | S. Delhoumi |
| 2236 | Groupe 7 + Groupe 8 | E. Porcq |

> 📌 **Simon Clément** fait partie du **Groupe 5**, salle **2127**, coach **C. Passoni-Chevalier**.

- Le **coach** (Scrum Master) aide à s'organiser mais n'intervient pas techniquement.
- Le **client** évalue le produit final, ne connaît pas la technique.
- Règles d'équipe : personne laissé de côté, tout le monde sait ce que font les autres, dépôt Git partagé, chef d'équipe optionnel.

---

## 🔁 Méthode agile utilisée

- **5 itérations (sprints)** réparties sur **3 jours** (24h de projet tutoré)
- Pratiques mises en œuvre :
  - **Itération 0** : mise en place équipe + environnement (Git...), objectif minimal = 1 fonctionnalité montrable
  - **Tableau des tâches** (Backlog / À faire / En cours / Terminée), à tenir à jour, éviter trop de tâches "en cours"
  - **Recette** : démo au client, qui vérifie les fonctionnalités promises et les retours précédents
  - **Rétrospective** après chaque recette (tableau Bien / Moins bien / Question à creuser / Qu'en tirer ?)
  - **Stand-up meeting** quotidien (15 min max, debout, 3 questions : hier / aujourd'hui / blocages)

### Planning type (Groupe A / Groupe B, horaires décalés)

| Jour | Étapes principales |
|------|--------------------|
| **Jour 1** | Accueil, choix des fonctionnalités, début dev, Démo 1, rétro, Démo 2, rétro |
| **Jour 2** | Stand-up, Démo 3, rétro, Démo 4, rétro |
| **Jour 3** | Démo finale, rétro, **présentation en amphi (15 min/équipe)** |

---

## 📊 Évaluation

Notation par **équipe**, sur les 3 jours :

| Évaluateur | Critère | Points |
|------------|---------|--------|
| Coach (tuteur) | Implication dans l'activité | 5 |
| Coach (tuteur) | Organisation de l'équipe | 5 |
| Client | Tenue des promesses / Qualité de la présentation | 3 |
| Client | Qualité du produit et réponse au besoin | 7 |

### Présentation finale (15 min/équipe)
- 2 min max de présentation de "l'entreprise" (qui sommes-nous, nos atouts)
- Démonstration du produit, préparée à l'avance (pas d'improvisation, pas de données bidon)
- Aucune question posée par l'équipe cliente

### Livrables attendus
- Démonstration évaluée par l'équipe cliente
- Note de suivi du professeur tuteur
- Notes SAÉ dans les ressources associées (MCD, script SQL, requêtes, etc.)

---

## ✅ Droits et devoirs de l'équipe

- **Droits** : droit à l'erreur, à trouver ses propres solutions, à négocier avec le client
- **Devoirs** : s'améliorer en continu, satisfaire le client, résoudre ses propres problèmes

---

## 📌 À faire avant le projet

- [ ] Compléter le MCD de la base de données (TD du 30/03/2026)
- [ ] Générer le MLD + script SQL, exécuter sur SQL Developer
- [ ] Insérer les départements depuis le fichier tableur fourni
- [ ] Réaliser les 20 requêtes SQL demandées + partie "voyage le plus long"
- [ ] Réaliser le TP noté R2.06
- [ ] Préparer l'environnement de dev (Git, accès serveur Web université)
- [ ] Bien connaître le backlog et les priorités des fonctionnalités
