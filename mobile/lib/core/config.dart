/// Adresse du serveur Laravel.
/// - En local sur émulateur Android : http://10.0.2.2:8000
/// - En local sur téléphone réel (même Wi-Fi que le Mac) : http://192.168.x.x:8000
/// - En production : https://app.votre-domaine.com
///
/// On peut aussi la passer à la compilation :
///   flutter run --dart-define=API_URL=https://app.votre-domaine.com
class AppConfig {
  static const String apiUrl = String.fromEnvironment('API_URL', defaultValue: 'http://10.0.2.2:8000');
  static const String appName = 'Fabara Gestion';

  /// Intervalle de synchronisation automatique quand le réseau est disponible
  static const Duration syncEvery = Duration(minutes: 2);
}
