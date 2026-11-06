#!/usr/bin/env bash
# =============================================================
#  Fabara Gestion — installation du site Laravel (Mac / Linux)
#  Usage :  bash install-backend.sh
#  Prérequis : PHP 8.2+, Composer  (Mac : brew install php composer)
# =============================================================
set -e
cd "$(dirname "$0")"
ROOT="$(pwd)"
APP="$ROOT/fabara-gestion-web"

echo "▶ 1/6  Création du projet Laravel…"
if [ ! -d "$APP" ]; then
  composer create-project laravel/laravel "$APP" --no-interaction
fi
cd "$APP"

echo "▶ 2/6  Paquets : Sanctum (API mobile) + DomPDF (reçus PDF)…"
composer require laravel/sanctum barryvdh/laravel-dompdf --no-interaction
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction || true

echo "▶ 3/6  Copie du code Fabara Gestion…"
cp -R "$ROOT/backend/." "$APP/"
rm -f "$APP/resources/views/welcome.blade.php"

echo "▶ 4/6  Configuration (.env)…"
[ -f .env ] || cp .env.example .env
perl -pi -e 's/^APP_NAME=.*/APP_NAME="Fabara Gestion"/; s/^APP_LOCALE=.*/APP_LOCALE=fr/; s/^APP_FALLBACK_LOCALE=.*/APP_FALLBACK_LOCALE=fr/; s/^APP_FAKER_LOCALE=.*/APP_FAKER_LOCALE=fr_FR/' .env
grep -q '^SAAS_NAME=' .env || cat >> .env <<'EOF'

# --- Fabara Gestion ---
SAAS_NAME="Fabara Gestion"
SAAS_SUPPORT_PHONE="626 43 22 76"
SAAS_SUPPORT_WHATSAPP=224626432276
SAAS_TRIAL_DAYS=14
SUPERADMIN_EMAIL=admin@fabara-gestion.com
SUPERADMIN_PASSWORD="ChangeMoi2026!"
EOF
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

# Messages d'erreur en français (facultatif)
composer require laravel-lang/common --dev --no-interaction && php artisan lang:add fr || echo "  (traductions FR ignorées)"

echo "▶ 5/6  Base de données + données de démonstration…"
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan storage:link || true
php artisan migrate:fresh --seed --force

echo "▶ 6/6  Terminé ✅"
echo ""
echo "  Lancer le site :   cd fabara-gestion-web && php artisan serve --host=0.0.0.0"
echo "  Ouvrir :           http://localhost:8000"
echo ""
echo "  Comptes de démonstration :"
echo "   • Toi (super-admin)    admin@fabara-gestion.com / ChangeMoi2026!"
echo "   • Propriétaire         proprio@demo.gn / demo1234"
echo "   • Gérante              vendeuse@demo.gn / demo1234"
