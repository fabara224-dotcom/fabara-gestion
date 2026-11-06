class Product {
  final String id;
  String name;
  String? categoryId;
  String? sku;
  String? barcode;
  int price;
  int cost;
  String unit;
  double stock;
  double alertThreshold;
  String? imageUrl;
  String? localImage; // photo prise sur le téléphone, pas encore envoyée
  bool trackStock;
  bool isActive;

  Product({
    required this.id, required this.name, this.categoryId, this.sku, this.barcode, required this.price,
    this.cost = 0, this.unit = 'pièce', this.stock = 0, this.alertThreshold = 3, this.imageUrl, this.localImage,
    this.trackStock = true, this.isActive = true,
  });

  bool get isLow => trackStock && stock <= alertThreshold;

  factory Product.fromDb(Map<String, Object?> m) => Product(
        id: m['id'] as String, name: m['name'] as String, categoryId: m['category_id'] as String?,
        sku: m['sku'] as String?, barcode: m['barcode'] as String?, price: (m['price'] as num).toInt(),
        cost: (m['cost'] as num? ?? 0).toInt(), unit: (m['unit'] as String?) ?? 'pièce',
        stock: (m['stock'] as num? ?? 0).toDouble(), alertThreshold: (m['alert_threshold'] as num? ?? 0).toDouble(),
        imageUrl: m['image_url'] as String?, localImage: m['local_image'] as String?,
        trackStock: (m['track_stock'] as int? ?? 1) == 1, isActive: (m['is_active'] as int? ?? 1) == 1,
      );

  Map<String, Object?> toDb() => {
        'id': id, 'name': name, 'category_id': categoryId, 'sku': sku, 'barcode': barcode, 'price': price,
        'cost': cost, 'unit': unit, 'stock': stock, 'alert_threshold': alertThreshold, 'image_url': imageUrl,
        'local_image': localImage, 'track_stock': trackStock ? 1 : 0, 'is_active': isActive ? 1 : 0,
      };

  Map<String, dynamic> toApi() => {
        'id': id, 'name': name, 'category_id': categoryId, 'sku': sku, 'barcode': barcode, 'price': price,
        'cost': cost, 'unit': unit, 'alert_threshold': alertThreshold, 'track_stock': trackStock, 'is_active': isActive,
      };
}

class Category {
  final String id;
  final String name;
  final String? color;
  Category({required this.id, required this.name, this.color});
  factory Category.fromDb(Map<String, Object?> m) => Category(id: m['id'] as String, name: m['name'] as String, color: m['color'] as String?);
}

class CartLine {
  final Product product;
  double quantity;
  CartLine(this.product, [this.quantity = 1]);
  int get total => (product.price * quantity).round();
}

class SaleItem {
  final String id;
  final String? productId;
  final String name;
  final double quantity;
  final int unitPrice;
  final int total;
  SaleItem({required this.id, this.productId, required this.name, required this.quantity, required this.unitPrice, required this.total});

  factory SaleItem.fromDb(Map<String, Object?> m) => SaleItem(
        id: m['id'] as String, productId: m['product_id'] as String?, name: m['name'] as String,
        quantity: (m['quantity'] as num).toDouble(), unitPrice: (m['unit_price'] as num).toInt(), total: (m['total'] as num).toInt(),
      );
}

class Sale {
  final String id;
  String number;
  final String? customerName;
  final String? customerPhone;
  final int subtotal;
  final int discount;
  final int total;
  final int paid;
  final String paymentMethod;
  final DateTime soldAt;
  final String? seller;
  bool synced;
  String? receiptUrl;
  List<SaleItem> items;

  Sale({
    required this.id, required this.number, this.customerName, this.customerPhone, required this.subtotal,
    this.discount = 0, required this.total, required this.paid, required this.paymentMethod, required this.soldAt,
    this.seller, this.synced = false, this.receiptUrl, this.items = const [],
  });

  int get change => paid > total ? paid - total : 0;
  int get due => total > paid ? total - paid : 0;

  factory Sale.fromDb(Map<String, Object?> m, [List<SaleItem> items = const []]) => Sale(
        id: m['id'] as String, number: m['number'] as String, customerName: m['customer_name'] as String?,
        customerPhone: m['customer_phone'] as String?, subtotal: (m['subtotal'] as num).toInt(),
        discount: (m['discount'] as num? ?? 0).toInt(), total: (m['total'] as num).toInt(), paid: (m['paid'] as num).toInt(),
        paymentMethod: m['payment_method'] as String, soldAt: DateTime.parse(m['sold_at'] as String),
        seller: m['seller'] as String?, synced: (m['synced'] as int? ?? 0) == 1, receiptUrl: m['receipt_url'] as String?, items: items,
      );

  Map<String, dynamic> toApi() => {
        'id': id, 'number': number, 'customer_name': customerName, 'customer_phone': customerPhone,
        'discount': discount, 'paid': paid, 'payment_method': paymentMethod, 'sold_at': soldAt.toUtc().toIso8601String(),
        'items': items.map((i) => {'id': i.id, 'product_id': i.productId, 'name': i.name, 'quantity': i.quantity, 'unit_price': i.unitPrice}).toList(),
      };
}

class TenantInfo {
  final String name;
  final String? logoUrl;
  final String? brandColor;
  final String? phone;
  final String? address;
  final String? city;
  final String? receiptHeader;
  final String? receiptFooter;
  final String? taxId;
  final String currency;
  final String status;
  final int? daysLeft;

  TenantInfo({required this.name, this.logoUrl, this.brandColor, this.phone, this.address, this.city, this.receiptHeader,
      this.receiptFooter, this.taxId, this.currency = 'GNF', this.status = 'active', this.daysLeft});

  factory TenantInfo.fromJson(Map<String, dynamic> j) => TenantInfo(
        name: j['name'] ?? '', logoUrl: j['logo_url'], brandColor: j['brand_color'], phone: j['phone'], address: j['address'],
        city: j['city'], receiptHeader: j['receipt_header'], receiptFooter: j['receipt_footer'], taxId: j['tax_id'],
        currency: j['currency'] ?? 'GNF', status: j['status'] ?? 'active', daysLeft: j['days_left'],
      );

  Map<String, dynamic> toJson() => {
        'name': name, 'logo_url': logoUrl, 'brand_color': brandColor, 'phone': phone, 'address': address, 'city': city,
        'receipt_header': receiptHeader, 'receipt_footer': receiptFooter, 'tax_id': taxId, 'currency': currency,
        'status': status, 'days_left': daysLeft,
      };
}

class AppUser {
  final int id;
  final String name;
  final String email;
  final String role; // owner, manager, cashier
  final String roleLabel;
  final String? branch;

  AppUser({required this.id, required this.name, required this.email, required this.role, required this.roleLabel, this.branch});

  bool get isOwner => role == 'owner';
  bool get canManage => role == 'owner' || role == 'manager';

  factory AppUser.fromJson(Map<String, dynamic> j) => AppUser(
        id: j['id'], name: j['name'], email: j['email'], role: j['role'], roleLabel: j['role_label'] ?? j['role'], branch: j['branch']);

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'email': email, 'role': role, 'role_label': roleLabel, 'branch': branch};
}
