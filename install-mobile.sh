#!/usr/bin/env bash
# =============================================================
#  Fabara Gestion — préparation de l'application Flutter
#  Usage :  bash install-mobile.sh
#  Prérequis : Flutter 3.27+ (flutter doctor doit être OK)
# =============================================================
set -e
cd "$(dirname "$0")/mobile"

echo "▶ Génération des dossiers Android / iOS…"
flutter create . --org com.fabara --project-name fabara_gestion --platforms android,ios
rm -f test/widget_test.dart   # test d'exemple généré par Flutter (inutile ici)

echo "▶ Permissions Android (internet, appareil photo)…"
MANIFEST=android/app/src/main/AndroidManifest.xml
grep -q 'android.permission.INTERNET' "$MANIFEST" || perl -0pi -e 's|<application|<uses-permission android:name="android.permission.INTERNET"/>\n    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE"/>\n    <uses-permission android:name="android.permission.CAMERA"/>\n    <application|' "$MANIFEST"
# autorise http:// pour les tests en local (à retirer en production si tout est en https)
grep -q 'usesCleartextTraffic' "$MANIFEST" || perl -0pi -e 's|<application|<application android:usesCleartextTraffic="true"|' "$MANIFEST"

echo "▶ Autorisations iOS (photo, galerie)…"
PLIST=ios/Runner/Info.plist
if ! grep -q NSCameraUsageDescription "$PLIST"; then
  perl -0pi -e 's|<dict>|<dict>\n\t<key>NSCameraUsageDescription</key>\n\t<string>Pour photographier vos articles.</string>\n\t<key>NSPhotoLibraryUsageDescription</key>\n\t<string>Pour choisir la photo d\x27un article.</string>|' "$PLIST"
fi

echo "▶ Dépendances…"
flutter pub get

echo ""
echo "✅ Prêt."
echo "  Émulateur Android (site lancé sur le Mac) :"
echo "     flutter run"
echo "  Téléphone réel sur le même Wi-Fi (remplace par l'IP de ton Mac) :"
echo "     flutter run --dart-define=API_URL=http://192.168.1.20:8000"
echo "  Version Play Store (production) :"
echo "     flutter build appbundle --dart-define=API_URL=https://app.ton-domaine.com"
