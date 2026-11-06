import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';

import '../models/models.dart';

/// Base de données locale SQLite : tout est enregistré sur le téléphone d'abord,
/// puis envoyé au serveur dès que le réseau revient.
class LocalDb {
  static final LocalDb instance = LocalDb._();
  LocalDb._();
  Database? _db;
  static const _uuid = Uuid();

  Future<Database> get db async => _db ??= await openDatabase(
        p.join(await getDatabasesPath(), 'fabara_gestion.db'),
        version: 1,
        onCreate: (d, v) async {
          await d.execute('''CREATE TABLE products(id TEXT PRIMARY KEY, category_id TEXT, name TEXT NOT NULL, sku TEXT, barcode TEXT,
            price INTEGER NOT NULL, cost INTEGER DEFAULT 0, unit TEXT, stock REAL DEFAULT 0, alert_threshold REAL DEFAULT 0,
            image_url TEXT, local_image TEXT, track_stock INTEGER DEFAULT 1, is_active INTEGER DEFAULT 1,
            dirty INTEGER DEFAULT 0, image_dirty INTEGER DEFAULT 0)''');
          await d.execute('CREATE INDEX idx_products_name ON products(name)');
          await d.execute('CREATE TABLE categories(id TEXT PRIMARY KEY, name TEXT, color TEXT)');
          await d.execute('CREATE TABLE customers(id TEXT PRIMARY KEY, name TEXT, phone TEXT, balance INTEGER DEFAULT 0)');
          await d.execute('''CREATE TABLE sales(id TEXT PRIMARY KEY, number TEXT, customer_name TEXT, customer_phone TEXT,
            subtotal INTEGER, discount INTEGER, total INTEGER, paid INTEGER, payment_method TEXT, sold_at TEXT,
            seller TEXT, synced INTEGER DEFAULT 0, receipt_url TEXT)''');
          await d.execute('CREATE INDEX idx_sales_date ON sales(sold_at)');
          await d.execute('''CREATE TABLE sale_items(id TEXT PRIMARY KEY, sale_id TEXT, product_id TEXT, name TEXT,
            quantity REAL, unit_price INTEGER, total INTEGER)''');
          await d.execute('''CREATE TABLE stock_movements(id TEXT PRIMARY KEY, product_id TEXT, type TEXT, quantity REAL,
            reason TEXT, created_at TEXT, synced INTEGER DEFAULT 0)''');
          await d.execute('''CREATE TABLE expenses(id TEXT PRIMARY KEY, label TEXT, category TEXT, amount INTEGER,
            spent_on TEXT, synced INTEGER DEFAULT 0)''');
          await d.execute('CREATE TABLE meta(key TEXT PRIMARY KEY, value TEXT)');
        },
      );

  // ---------- meta ----------
  Future<String?> getMeta(String key) async {
    final r = await (await db).query('meta', where: 'key = ?', whereArgs: [key]);
    return r.isEmpty ? null : r.first['value'] as String?;
  }

  Future<void> setMeta(String key, String? value) async =>
      (await db).insert('meta', {'key': key, 'value': value}, conflictAlgorithm: ConflictAlgorithm.replace);

  // ---------- produits ----------
  Future<List<Product>> products({String? search, String? categoryId, bool activeOnly = false, bool lowOnly = false}) async {
    final where = <String>[];
    final args = <Object?>[];
    if (search != null && search.trim().isNotEmpty) {
      where.add('(name LIKE ? OR barcode = ? OR sku LIKE ?)');
      args..add('%${search.trim()}%')..add(search.trim())..add('%${search.trim()}%');
    }
    if (categoryId != null) { where.add('category_id = ?'); args.add(categoryId); }
    if (activeOnly) where.add('is_active = 1');
    if (lowOnly) where.add('track_stock = 1 AND stock <= alert_threshold');
    final rows = await (await db).query('products', where: where.isEmpty ? null : where.join(' AND '), whereArgs: args, orderBy: 'name COLLATE NOCASE');
    return rows.map(Product.fromDb).toList();
  }

  Future<Product?> productByBarcode(String code) async {
    final r = await (await db).query('products', where: 'barcode = ?', whereArgs: [code], limit: 1);
    return r.isEmpty ? null : Product.fromDb(r.first);
  }

  /// Article créé ou modifié sur le téléphone → marqué "à envoyer"
  Future<void> saveProductLocal(Product prod, {bool imageChanged = false}) async {
    final d = await db;
    final existing = await d.query('products', columns: ['image_dirty'], where: 'id = ?', whereArgs: [prod.id]);
    final wasDirtyImage = existing.isNotEmpty && existing.first['image_dirty'] == 1;
    await d.insert('products', {...prod.toDb(), 'dirty': 1, 'image_dirty': (imageChanged || wasDirtyImage) ? 1 : 0},
        conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<List<Category>> categories() async =>
      (await (await db).query('categories', orderBy: 'name COLLATE NOCASE')).map(Category.fromDb).toList();

  // ---------- ventes ----------
  /// Enregistre la vente sur le téléphone et décrémente le stock local immédiatement.
  Future<Sale> createSale({
    required List<CartLine> lines, required String number, required String paymentMethod, required int paid,
    int discount = 0, String? customerName, String? customerPhone, String? seller,
  }) async {
    final d = await db;
    final id = _uuid.v4();
    final subtotal = lines.fold<int>(0, (s, l) => s + l.total);
    final total = subtotal - discount < 0 ? 0 : subtotal - discount;
    final now = DateTime.now();
    final items = lines.map((l) => SaleItem(id: _uuid.v4(), productId: l.product.id, name: l.product.name,
        quantity: l.quantity, unitPrice: l.product.price, total: l.total)).toList();

    await d.transaction((tx) async {
      await tx.insert('sales', {
        'id': id, 'number': number, 'customer_name': customerName, 'customer_phone': customerPhone, 'subtotal': subtotal,
        'discount': discount, 'total': total, 'paid': paid, 'payment_method': paymentMethod, 'sold_at': now.toIso8601String(),
        'seller': seller, 'synced': 0,
      });
      for (final it in items) {
        await tx.insert('sale_items', {'id': it.id, 'sale_id': id, 'product_id': it.productId, 'name': it.name,
            'quantity': it.quantity, 'unit_price': it.unitPrice, 'total': it.total});
      }
      for (final l in lines.where((l) => l.product.trackStock)) {
        await tx.rawUpdate('UPDATE products SET stock = stock - ? WHERE id = ?', [l.quantity, l.product.id]);
      }
    });

    return Sale(id: id, number: number, customerName: customerName, customerPhone: customerPhone, subtotal: subtotal,
        discount: discount, total: total, paid: paid, paymentMethod: paymentMethod, soldAt: now, seller: seller, items: items);
  }

  Future<List<SaleItem>> saleItems(String saleId) async =>
      (await (await db).query('sale_items', where: 'sale_id = ?', whereArgs: [saleId])).map(SaleItem.fromDb).toList();

  Future<List<Sale>> sales({DateTime? day, bool unsyncedOnly = false, int limit = 200}) async {
    final where = <String>[];
    final args = <Object?>[];
    if (day != null) {
      final start = DateTime(day.year, day.month, day.day);
      where.add('sold_at >= ? AND sold_at < ?');
      args..add(start.toIso8601String())..add(start.add(const Duration(days: 1)).toIso8601String());
    }
    if (unsyncedOnly) where.add('synced = 0');
    final rows = await (await db).query('sales', where: where.isEmpty ? null : where.join(' AND '), whereArgs: args, orderBy: 'sold_at DESC', limit: limit);
    final list = <Sale>[];
    for (final r in rows) {
      list.add(Sale.fromDb(r, await saleItems(r['id'] as String)));
    }
    return list;
  }

  Future<void> markSaleSynced(String id, String number, String? receiptUrl) async =>
      (await db).update('sales', {'synced': 1, 'number': number, 'receipt_url': receiptUrl}, where: 'id = ?', whereArgs: [id]);

  Future<int> pendingCount() async {
    final d = await db;
    int c(List<Map<String, Object?>> r) => (r.first['c'] as int?) ?? 0;
    return c(await d.rawQuery('SELECT COUNT(*) c FROM sales WHERE synced = 0')) +
        c(await d.rawQuery('SELECT COUNT(*) c FROM products WHERE dirty = 1 OR image_dirty = 1')) +
        c(await d.rawQuery('SELECT COUNT(*) c FROM stock_movements WHERE synced = 0')) +
        c(await d.rawQuery('SELECT COUNT(*) c FROM expenses WHERE synced = 0'));
  }

  // ---------- stock ----------
  Future<void> addStockMovement(Product prod, String type, double quantity, String? reason) async {
    final d = await db;
    final delta = switch (type) { 'in' => quantity, 'out' => -quantity, _ => quantity - prod.stock };
    await d.transaction((tx) async {
      await tx.insert('stock_movements', {'id': _uuid.v4(), 'product_id': prod.id, 'type': type, 'quantity': delta,
          'reason': reason, 'created_at': DateTime.now().toIso8601String(), 'synced': 0});
      await tx.rawUpdate('UPDATE products SET stock = stock + ? WHERE id = ?', [delta, prod.id]);
    });
  }

  Future<void> addExpense(String label, String category, int amount) async => (await db).insert('expenses', {
        'id': _uuid.v4(), 'label': label, 'category': category, 'amount': amount,
        'spent_on': DateTime.now().toIso8601String().substring(0, 10), 'synced': 0,
      });

  // ---------- application des données du serveur ----------
  /// Le stock serveur ne connaît pas encore les ventes non envoyées : on les retire.
  Future<double> _pendingSoldQty(DatabaseExecutor tx, String productId) async {
    final r = await tx.rawQuery('''SELECT COALESCE(SUM(i.quantity),0) q FROM sale_items i JOIN sales s ON s.id = i.sale_id
        WHERE s.synced = 0 AND i.product_id = ?''', [productId]);
    final m = await tx.rawQuery('SELECT COALESCE(SUM(quantity),0) q FROM stock_movements WHERE synced = 0 AND product_id = ?', [productId]);
    return (r.first['q'] as num).toDouble() - (m.first['q'] as num).toDouble();
  }

  Future<void> applyPull(Map<String, dynamic> data) async {
    final d = await db;
    await d.transaction((tx) async {
      for (final c in (data['categories'] as List? ?? [])) {
        if (c['deleted_at'] != null) {
          await tx.delete('categories', where: 'id = ?', whereArgs: [c['id']]);
        } else {
          await tx.insert('categories', {'id': c['id'], 'name': c['name'], 'color': c['color']}, conflictAlgorithm: ConflictAlgorithm.replace);
        }
      }
      for (final p in (data['products'] as List? ?? [])) {
        if (p['deleted_at'] != null) {
          await tx.delete('products', where: 'id = ?', whereArgs: [p['id']]);
          continue;
        }
        final local = await tx.query('products', columns: ['dirty', 'local_image', 'image_dirty'], where: 'id = ?', whereArgs: [p['id']]);
        if (local.isNotEmpty && local.first['dirty'] == 1) continue; // modification locale prioritaire, envoyée au prochain push
        final pending = await _pendingSoldQty(tx, p['id']);
        await tx.insert('products', {
          'id': p['id'], 'category_id': p['category_id'], 'name': p['name'], 'sku': p['sku'], 'barcode': p['barcode'],
          'price': p['price'], 'cost': p['cost'] ?? 0, 'unit': p['unit'], 'stock': (p['stock'] as num).toDouble() - pending,
          'alert_threshold': p['alert_threshold'], 'image_url': p['image_url'],
          'local_image': local.isNotEmpty ? local.first['local_image'] : null,
          'track_stock': p['track_stock'] == true ? 1 : 0, 'is_active': p['is_active'] == true ? 1 : 0,
          'dirty': 0, 'image_dirty': local.isNotEmpty ? local.first['image_dirty'] : 0,
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      }
      for (final c in (data['customers'] as List? ?? [])) {
        if (c['deleted_at'] != null) {
          await tx.delete('customers', where: 'id = ?', whereArgs: [c['id']]);
        } else {
          await tx.insert('customers', {'id': c['id'], 'name': c['name'], 'phone': c['phone'], 'balance': c['balance']},
              conflictAlgorithm: ConflictAlgorithm.replace);
        }
      }
    });
  }

  Future<List<Map<String, Object?>>> dirtyProducts() async => (await db).query('products', where: 'dirty = 1');
  Future<List<Map<String, Object?>>> dirtyImages() async => (await db).query('products', where: 'image_dirty = 1 AND local_image IS NOT NULL');
  Future<List<Map<String, Object?>>> unsynced(String table) async => (await db).query(table, where: 'synced = 0');

  Future<void> markProductsClean(List<String> ids) async {
    if (ids.isEmpty) return;
    await (await db).rawUpdate('UPDATE products SET dirty = 0 WHERE id IN (${List.filled(ids.length, '?').join(',')})', ids);
  }

  Future<void> markImageUploaded(String id, String url) async =>
      (await db).update('products', {'image_dirty': 0, 'image_url': url}, where: 'id = ?', whereArgs: [id]);

  Future<void> markSynced(String table, List<String> ids) async {
    if (ids.isEmpty) return;
    await (await db).rawUpdate('UPDATE $table SET synced = 1 WHERE id IN (${List.filled(ids.length, '?').join(',')})', ids);
  }

  /// Déconnexion : on efface les données de l'entreprise du téléphone (sauf ventes non envoyées → bloqué avant)
  Future<void> wipe() async {
    final d = await db;
    for (final t in ['products', 'categories', 'customers', 'sales', 'sale_items', 'stock_movements', 'expenses', 'meta']) {
      await d.delete(t);
    }
  }
}
