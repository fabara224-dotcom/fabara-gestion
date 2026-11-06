# Fabara Gestion

Logiciel de gestion à louer à plusieurs entreprises (SaaS multi-entreprises), pensé pour les boutiques de quartier, les shops, le prêt-à-porter, les quincailleries, les pharmacies, les cosmétiques et la téléphonie. Il est conçu pour la Guinée et l'Afrique de l'Ouest.

Le projet contient deux parties :

| Dossier | Contenu |
|---|---|
| `backend/` | Site **Laravel** : site vitrine, inscription des entreprises, caisse web, stock avec photos, reçus PDF/WhatsApp, rapports, espace propriétaire, ton espace super-admin, API pour le mobile |
| `mobile/` | Application **Flutter** (Android et iOS) : caisse **hors ligne**, stock avec photo, reçu imprimé ou PDF pour l'acheteur, chiffres du propriétaire, synchronisation automatique |

---

## 1. Installation sur ton Mac

### Site Laravel
```bash
brew install php composer        # si ce n'est pas déjà fait
bash install-backend.sh
cd fabara-gestion-web && php artisan serve --host=0.0.0.0
```
Ouvre ensuite http://localhost:8000

| Compte de démo | E-mail | Mot de passe |
|---|---|---|
| Toi (super-admin de la plateforme) | admin@fabara-gestion.com | ChangeMoi2026! |
| Propriétaire de la boutique démo | proprio@demo.gn | demo1234 |
| Gérante de la boutique démo | vendeuse@demo.gn | demo1234 |

> Change le mot de passe super-admin dans `.env` (`SUPERADMIN_PASSWORD`) avant la mise en ligne.

### Application Flutter
```bash
bash install-mobile.sh
cd mobile
flutter run                                              # émulateur Android
flutter run --dart-define=API_URL=http://IP-DU-MAC:8000  # vrai téléphone sur le même Wi-Fi
```

---

## 2. Qui voit quoi

| Rôle | Voit | Peut faire |
|---|---|---|
| **Super-admin** (toi) | Toutes les entreprises clientes : statut, formule, paiements, revenu mensuel | Activer, suspendre, prolonger, enregistrer un paiement Orange Money ou MoMo, « entrer » dans un espace pour aider un client |
| **Propriétaire** | **Uniquement son entreprise** : toutes ses boutiques, les ventes de chaque vendeur en direct, les bénéfices, le stock | Créer et désactiver des vendeurs, ajouter son logo et sa couleur, personnaliser les reçus, ajouter des boutiques |
| **Gérant(e)** | Sa boutique | Caisse, stock, articles et photos, dépenses, clients à crédit, rapports |
| **Vendeur(se)** | Ses propres ventes | Caisse et reçus |

**Isolation des données.** Chaque table porte un `tenant_id`. Le trait `BelongsToTenant` ajoute automatiquement le filtre à **toutes** les requêtes. Le middleware `SetTenant` s'exécute avant la résolution des URL, donc même une URL devinée (`/ventes/{id}`) ne s'ouvre pas pour une autre entreprise.

---

## 3. Hors ligne : comment ça marche

1. Le téléphone garde une copie des articles dans **SQLite**.
2. Chaque vente est enregistrée **sur le téléphone** avec un identifiant UUID et un numéro de reçu propre à l'appareil, par exemple `K7Q2-000012`. Le reçu PDF s'imprime ou se partage **sans réseau**.
3. Dès que le réseau revient, l'application envoie tout (`POST /api/v1/sync/push`) puis récupère les changements (`GET /api/v1/sync/pull?since=…`). Elle le fait aussi automatiquement toutes les 2 minutes.
4. Le stock est calculé à partir de **mouvements** (+5, −2). Deux téléphones qui vendent hors ligne en même temps ne s'écrasent donc pas.
5. Renvoyer une vente déjà reçue ne crée pas de doublon, car le serveur reconnaît son UUID.

---

## 4. Le reçu de l'acheteur

Après chaque vente, le vendeur a plusieurs façons de remettre le reçu :
- **Ticket 80 mm**, pour une imprimante thermique Bluetooth ou USB
- **PDF A4/A5**, à télécharger ou à partager
- **WhatsApp** : un lien `https://ton-domaine/r/xxxx` est envoyé à l'acheteur. Il voit son reçu avec le logo de la boutique, sans compte, et ne voit rien d'autre.
- Un QR code sur le ticket permet de vérifier le reçu en ligne.

---

## 5. Préparer la plateforme pour plus de 1000 entreprises

Le code est déjà prêt pour cette échelle :
- des index `(tenant_id, updated_at)` et `(tenant_id, sold_at)` sur les grosses tables ;
- une synchronisation incrémentale (seulement ce qui a changé) ;
- une limitation du nombre de requêtes par utilisateur ;
- des photos rangées par entreprise ;
- une pagination partout.

Pour la production, sur ton VPS LWS (Ubuntu 24.04) :

| Élément | En local | En production |
|---|---|---|
| Base de données | SQLite | **MySQL 8** ou PostgreSQL (`DB_CONNECTION=mysql`) |
| Cache / sessions / files d'attente | fichier | **Redis** (`CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`) |
| Photos | `storage/app/public` | Même chose au début, puis un stockage objet compatible S3 quand le volume grossit |
| Serveur web | `php artisan serve` | Nginx + PHP-FPM 8.3, HTTPS avec Let's Encrypt |
| Sauvegardes | — | `mysqldump` quotidien + copie des photos hors du serveur |

Repères de dimensionnement :
- **1000 entreprises** : un VPS de 4 vCPU et 8 Go de RAM suffit largement.
- **Au-delà de 5000** : séparer la base de données sur un serveur dédié.

Commandes de mise en production :
```bash
php artisan migrate --force
php artisan db:seed --force          # crée les formules et ton compte super-admin (pas de démo en production)
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link
```
Mets `APP_ENV=production`, `APP_DEBUG=false` et `APP_URL=https://ton-domaine.com`.

---

## 6. Formules de location (modifiables)

Elles se trouvent dans `backend/config/saas.php`. Après modification, relance `php artisan db:seed --force`.

| Formule | Prix / mois | Utilisateurs | Boutiques | Articles |
|---|---|---|---|---|
| Boutique | 150 000 GNF | 2 | 1 | 500 |
| Commerce | 300 000 GNF | 6 | 2 | 5 000 |
| Entreprise | 600 000 GNF | 25 | 10 | 100 000 |

Une nouvelle entreprise s'inscrit seule et commence avec un essai de 14 jours. Quand elle te paie par Orange Money ou MoMo, tu enregistres le paiement dans l'espace super-admin et l'abonnement est prolongé automatiquement. Sans paiement, l'accès est bloqué, mais les données sont conservées.

---

## 7. Structure du code

```
backend/
  app/Models/Concerns/BelongsToTenant.php   ← isolation automatique des données
  app/Http/Middleware/SetTenant.php          ← active l'entreprise de l'utilisateur
  app/Services/SaleService.php               ← vente, stock, numéro de reçu (web + mobile)
  app/Services/ReceiptService.php            ← reçus PDF ticket / A4
  app/Http/Controllers/Api/SyncController.php← synchronisation hors ligne
  app/Http/Controllers/Admin/…               ← ton espace super-admin
  resources/views/…                          ← interfaces (CSS dans public/css/app.css, sans compilation)
mobile/lib/
  core/local_db.dart      ← base SQLite du téléphone
  core/app_state.dart     ← connexion, réseau, synchronisation automatique
  services/receipt_pdf.dart ← reçu imprimable / partageable hors ligne
  screens/…               ← caisse, panier, reçu, stock, chiffres
```

## 8. Prochaines étapes conseillées
- Brancher le paiement automatique Orange Money / MTN MoMo (API marchand) pour que les clients paient leur abonnement seuls.
- Ajouter un scan de code-barres par la caméra (paquet `mobile_scanner`).
- Ajouter l'export Excel des rapports.
- Ajouter des notifications au propriétaire (grosse vente, stock épuisé).
