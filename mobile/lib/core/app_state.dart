import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

import '../models/models.dart';
import 'api_client.dart';
import 'config.dart';
import 'local_db.dart';

/// État global : session, réseau, synchronisation.
class AppState extends ChangeNotifier {
  final api = ApiClient();
  final db = LocalDb.instance;

  AppUser? user;
  TenantInfo? tenant;
  Uint8List? logoBytes;
  String deviceId = '';

  bool ready = false;
  bool online = false;
  bool syncing = false;
  int pending = 0;
  DateTime? lastSync;
  String? lastError;
  bool subscriptionBlocked = false;

  StreamSubscription? _conn;
  Timer? _timer;

  bool get loggedIn => user != null && api.token != null;

  /// Code court de l'appareil pour les numéros de reçus hors ligne (ex. « K7Q2 »)
  String get deviceCode => deviceId.replaceAll('-', '').substring(0, 4).toUpperCase();

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    deviceId = prefs.getString('device_id') ?? const Uuid().v4();
    await prefs.setString('device_id', deviceId);

    api.token = prefs.getString('token');
    final u = prefs.getString('user');
    final t = prefs.getString('tenant');
    if (u != null) user = AppUser.fromJson(jsonDecode(u));
    if (t != null) tenant = TenantInfo.fromJson(jsonDecode(t));
    final logo = prefs.getString('logo_b64');
    if (logo != null) logoBytes = base64Decode(logo);
    final ls = await db.getMeta('last_sync_at');
    if (ls != null) lastSync = DateTime.tryParse(ls);

    pending = await db.pendingCount();
    final status = await Connectivity().checkConnectivity();
    online = !status.contains(ConnectivityResult.none);

    _conn = Connectivity().onConnectivityChanged.listen((r) {
      final was = online;
      online = !r.contains(ConnectivityResult.none);
      notifyListeners();
      if (online && !was) sync(); // retour du réseau → synchro automatique
    });
    _timer = Timer.periodic(AppConfig.syncEvery, (_) => sync());

    ready = true;
    notifyListeners();
    if (loggedIn) sync();
  }

  @override
  void dispose() {
    _conn?.cancel();
    _timer?.cancel();
    super.dispose();
  }

  // ---------------- Connexion ----------------
  Future<void> login(String email, String password) async {
    final r = await api.post('/login', {'email': email.trim(), 'password': password, 'device_name': 'mobile-$deviceCode'});
    api.token = r['token'];
    await _saveProfile(r);
    await db.setMeta('last_pull', null); // première synchro complète
    await sync(force: true);
  }

  Future<void> _saveProfile(Map<String, dynamic> r) async {
    final prefs = await SharedPreferences.getInstance();
    user = AppUser.fromJson(Map<String, dynamic>.from(r['user']));
    tenant = TenantInfo.fromJson(Map<String, dynamic>.from(r['tenant']));
    if (api.token != null) await prefs.setString('token', api.token!);
    await prefs.setString('user', jsonEncode(user!.toJson()));
    await prefs.setString('tenant', jsonEncode(tenant!.toJson()));
    await _cacheLogo();
    notifyListeners();
  }

  /// Le logo est gardé sur le téléphone pour l'imprimer sur les reçus même hors ligne
  Future<void> _cacheLogo() async {
    final url = tenant?.logoUrl;
    final prefs = await SharedPreferences.getInstance();
    if (url == null) { logoBytes = null; await prefs.remove('logo_b64'); return; }
    try {
      final res = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 15));
      if (res.statusCode == 200) {
        logoBytes = res.bodyBytes;
        await prefs.setString('logo_b64', base64Encode(logoBytes!));
      }
    } catch (_) {}
  }

  Future<bool> logout({bool force = false}) async {
    await refreshPending();
    if (pending > 0 && !force) return false; // ne pas perdre de ventes non envoyées
    try { if (online) await api.post('/logout', {}); } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    for (final k in ['token', 'user', 'tenant', 'logo_b64']) { await prefs.remove(k); }
    api.token = null; user = null; tenant = null; logoBytes = null; lastSync = null;
    await db.wipe();
    pending = 0;
    notifyListeners();
    return true;
  }

  // ---------------- Ventes ----------------
  /// Numéro de reçu hors ligne unique par appareil : « K7Q2-000012 ».
  /// Le serveur le garde tel quel lors de la synchro.
  Future<String> nextLocalNumber() async {
    final n = int.tryParse(await db.getMeta('local_counter') ?? '0') ?? 0;
    await db.setMeta('local_counter', '${n + 1}');
    return '$deviceCode-${(n + 1).toString().padLeft(6, '0')}';
  }

  Future<void> refreshPending() async {
    pending = await db.pendingCount();
    notifyListeners();
  }

  // ---------------- Synchronisation ----------------
  Future<void> sync({bool force = false}) async {
    if (!loggedIn || syncing) return;
    if (!online && !force) return;
    syncing = true; lastError = null;
    notifyListeners();
    try {
      await _push();
      await _uploadImages();
      await _pull();
      lastSync = DateTime.now();
      await db.setMeta('last_sync_at', lastSync!.toIso8601String());
      subscriptionBlocked = false;
      online = true;
    } on ApiException catch (e) {
      lastError = e.message;
      if (e.isSubscription) subscriptionBlocked = true;
      if (e.isAuth) { await logout(force: false); }
    } on SocketException {
      online = false;
    } on http.ClientException {
      online = false;
    } on TimeoutException {
      lastError = 'Réseau trop lent, nouvel essai plus tard.';
    } catch (e) {
      lastError = e.toString();
    } finally {
      syncing = false;
      pending = await db.pendingCount();
      notifyListeners();
    }
  }

  Future<void> _push() async {
    final products = await db.dirtyProducts();
    final sales = <Map<String, dynamic>>[];
    for (final s in await db.sales(unsyncedOnly: true, limit: 300)) { sales.add(s.toApi()); }
    final moves = await db.unsynced('stock_movements');
    final expenses = await db.unsynced('expenses');
    if (products.isEmpty && sales.isEmpty && moves.isEmpty && expenses.isEmpty) return;

    final r = await api.post('/sync/push', {
      'device_id': deviceId,
      'products': products.map((p) => Product.fromDb(p).toApi()).toList(),
      'sales': sales,
      'stock_movements': moves.map((m) => {'id': m['id'], 'product_id': m['product_id'], 'type': m['type'], 'quantity': m['quantity'], 'reason': m['reason']}).toList(),
      'expenses': expenses.map((x) => {'id': x['id'], 'label': x['label'], 'category': x['category'], 'amount': x['amount'], 'spent_on': x['spent_on']}).toList(),
    });

    await db.markProductsClean(List<String>.from(r['products'] ?? []));
    for (final s in (r['sales'] as List? ?? [])) {
      await db.markSaleSynced(s['id'], s['number'], s['receipt_url']);
    }
    await db.markSynced('stock_movements', List<String>.from(r['stock_movements'] ?? []));
    await db.markSynced('expenses', List<String>.from(r['expenses'] ?? []));
    final errors = (r['errors'] as List? ?? []);
    if (errors.isNotEmpty) lastError = '${errors.length} élément(s) refusé(s) : ${errors.first['message']}';
  }

  Future<void> _uploadImages() async {
    if (!(user?.canManage ?? false)) return;
    for (final p in await db.dirtyImages()) {
      final f = File(p['local_image'] as String);
      if (!await f.exists()) continue;
      final r = await api.uploadImage('/products/${p['id']}/image', f);
      await db.markImageUploaded(p['id'] as String, r['image_url']);
    }
  }

  Future<void> _pull() async {
    final since = await db.getMeta('last_pull');
    final r = await api.get('/sync/pull', since == null ? null : {'since': since});
    await db.applyPull(r);
    await db.setMeta('last_pull', r['server_time']);
    if (r['tenant'] != null) {
      final oldLogo = tenant?.logoUrl;
      tenant = TenantInfo.fromJson(Map<String, dynamic>.from(r['tenant']));
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('tenant', jsonEncode(tenant!.toJson()));
      if (tenant!.logoUrl != oldLogo || logoBytes == null) await _cacheLogo();
    }
  }
}
