import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../widgets/common.dart';
import 'checkout_sheet.dart';
import 'receipt_screen.dart';

/// Caisse : toucher un article l'ajoute au panier. Fonctionne entièrement hors ligne.
class PosScreen extends StatefulWidget {
  const PosScreen({super.key});
  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  final _search = TextEditingController();
  List<Product> _products = [];
  List<Category> _cats = [];
  String? _cat;
  final Map<String, CartLine> _cart = {};
  DateTime? _seenSync;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final db = context.read<AppState>().db;
    final p = await db.products(search: _search.text, categoryId: _cat, activeOnly: true);
    final c = await db.categories();
    if (mounted) setState(() { _products = p; _cats = c; });
  }

  int get _total => _cart.values.fold(0, (s, l) => s + l.total);
  double get _count => _cart.values.fold(0.0, (s, l) => s + l.quantity);

  void _add(Product p) {
    HapticFeedback.selectionClick();
    setState(() => _cart.update(p.id, (l) => l..quantity += 1, ifAbsent: () => CartLine(p)));
  }

  Future<void> _onSubmitted(String v) async {
    final p = await context.read<AppState>().db.productByBarcode(v.trim());
    if (p != null) { _add(p); _search.clear(); _load(); }
  }

  Future<void> _openCart() async {
    final sale = await showModalBottomSheet<Sale>(
      context: context, isScrollControlled: true, useSafeArea: true, backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => CheckoutSheet(cart: _cart, onChanged: () => setState(() {})),
    );
    if (sale != null && mounted) {
      setState(() => _cart.clear());
      _load();
      await Navigator.of(context).push(MaterialPageRoute(builder: (_) => ReceiptScreen(sale: sale, justSold: true)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    if (s.lastSync != _seenSync) { _seenSync = s.lastSync; WidgetsBinding.instance.addPostFrameCallback((_) => _load()); }
    final width = MediaQuery.of(context).size.width;
    final cols = width > 900 ? 5 : width > 600 ? 4 : 2;

    return Scaffold(
      appBar: AppBar(
        title: Row(children: [
          if (s.logoBytes != null) Padding(padding: const EdgeInsets.only(right: 10),
              child: ClipRRect(borderRadius: BorderRadius.circular(9), child: Image.memory(s.logoBytes!, width: 34, height: 34, fit: BoxFit.cover))),
          Expanded(child: Text(s.tenant?.name ?? 'Caisse', overflow: TextOverflow.ellipsis)),
        ]),
        actions: const [SyncPill()],
      ),
      body: Column(children: [
        Container(
          color: Colors.white,
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 10),
          child: Column(children: [
            TextField(
              controller: _search, onChanged: (_) => _load(), onSubmitted: _onSubmitted, textInputAction: TextInputAction.search,
              decoration: InputDecoration(hintText: 'Rechercher ou scanner un code-barres', prefixIcon: const Icon(Icons.search),
                  suffixIcon: _search.text.isEmpty ? null : IconButton(icon: const Icon(Icons.close), onPressed: () { _search.clear(); _load(); })),
            ),
            const SizedBox(height: 10),
            SizedBox(height: 38, child: ListView(scrollDirection: Axis.horizontal, children: [
              _chip('Tout', _cat == null, () { _cat = null; _load(); }),
              for (final c in _cats) _chip(c.name, _cat == c.id, () { _cat = c.id; _load(); }, color: parseHex(c.color, AppColors.muted)),
            ])),
          ]),
        ),
        Expanded(
          child: _products.isEmpty
              ? EmptyState(icon: Icons.inventory_2_outlined, title: 'Aucun article',
                  text: s.user?.canManage == true ? 'Ajoutez vos articles dans l\'onglet Stock.' : 'Le gérant doit d\'abord ajouter les articles.')
              : RefreshIndicator(
                  onRefresh: () => s.sync(force: true),
                  child: GridView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 14, 16, 110),
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: cols, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: .72),
                    itemCount: _products.length,
                    itemBuilder: (_, i) => _ProductTile(p: _products[i], inCart: _cart[_products[i].id]?.quantity, onTap: () => _add(_products[i])),
                  ),
                ),
        ),
      ]),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: _cart.isEmpty
          ? null
          : Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _openCart,
                  style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(60), elevation: 4),
                  child: Row(children: [
                    Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(99)), child: Text(qty(_count))),
                    const SizedBox(width: 12),
                    const Text('Voir le panier'),
                    const Spacer(),
                    Text(money(_total, s.tenant?.currency ?? 'GNF'), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
                  ]),
                ),
              ),
            ),
    );
  }

  Widget _chip(String label, bool on, VoidCallback tap, {Color? color}) => Padding(
        padding: const EdgeInsets.only(right: 8),
        child: ChoiceChip(
          label: Text(label), selected: on, onSelected: (_) => setState(tap), showCheckmark: false,
          avatar: color == null ? null : CircleAvatar(backgroundColor: color, radius: 5),
          selectedColor: AppColors.ink, labelStyle: TextStyle(color: on ? Colors.white : AppColors.ink, fontWeight: FontWeight.w600),
          backgroundColor: Colors.white,
        ),
      );
}

class _ProductTile extends StatelessWidget {
  final Product p;
  final double? inCart;
  final VoidCallback onTap;
  const _ProductTile({required this.p, this.inCart, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final brand = Theme.of(context).colorScheme.primary;
    final cur = context.read<AppState>().tenant?.currency ?? 'GNF';
    return Material(
      color: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18),
          side: BorderSide(color: inCart != null ? brand : AppColors.line, width: inCart != null ? 2 : 1)),
      child: InkWell(
        borderRadius: BorderRadius.circular(18), onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(child: Stack(children: [
              Positioned.fill(child: ProductImage(p)),
              Positioned(left: 6, top: 6, child: StockBadge(p)),
              if (inCart != null) Positioned(right: 6, top: 6, child: CircleAvatar(radius: 14, backgroundColor: brand,
                  child: Text(qty(inCart), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 12)))),
            ])),
            const SizedBox(height: 8),
            Text(p.name, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5, height: 1.2)),
            const SizedBox(height: 2),
            Text(money(p.price, cur), style: TextStyle(color: brand, fontWeight: FontWeight.w800, fontSize: 14)),
          ]),
        ),
      ),
    );
  }
}
