import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../widgets/common.dart';
import 'product_form_screen.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({super.key});
  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  final _q = TextEditingController();
  bool _low = false;
  List<Product> _items = [];
  DateTime? _seen;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    final list = await context.read<AppState>().db.products(search: _q.text, lowOnly: _low);
    if (mounted) setState(() => _items = list);
  }

  Future<void> _edit([Product? p]) async {
    final changed = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => ProductFormScreen(product: p)));
    if (changed == true) _load();
  }

  Future<void> _adjust(Product p) async {
    final s = context.read<AppState>();
    String type = 'in';
    final qtyCtl = TextEditingController();
    final reasonCtl = TextEditingController();
    final ok = await showModalBottomSheet<bool>(
      context: context, isScrollControlled: true, backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(builder: (ctx, set) => Padding(
        padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + MediaQuery.of(ctx).viewInsets.bottom),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text(p.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
          Text('Stock actuel : ${qty(p.stock)} ${p.unit}', style: const TextStyle(color: AppColors.muted)),
          const SizedBox(height: 16),
          SegmentedButton<String>(
            segments: const [
              ButtonSegment(value: 'in', label: Text('Entrée'), icon: Icon(Icons.add)),
              ButtonSegment(value: 'out', label: Text('Sortie'), icon: Icon(Icons.remove)),
              ButtonSegment(value: 'adjust', label: Text('Inventaire'), icon: Icon(Icons.fact_check_outlined)),
            ],
            selected: {type}, onSelectionChanged: (v) => set(() => type = v.first),
          ),
          const SizedBox(height: 14),
          TextField(controller: qtyCtl, autofocus: true, keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(labelText: type == 'adjust' ? 'Quantité comptée en rayon' : 'Quantité')),
          const SizedBox(height: 10),
          TextField(controller: reasonCtl, decoration: const InputDecoration(labelText: 'Motif (arrivage, casse…)')),
          const SizedBox(height: 16),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Enregistrer')),
        ]),
      )),
    );
    final q = double.tryParse(qtyCtl.text.replaceAll(',', '.'));
    if (ok == true && q != null) {
      await s.db.addStockMovement(p, type, q, reasonCtl.text.isEmpty ? null : reasonCtl.text);
      await s.refreshPending();
      s.sync();
      _load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    if (s.lastSync != _seen) { _seen = s.lastSync; WidgetsBinding.instance.addPostFrameCallback((_) => _load()); }
    final manage = s.user?.canManage ?? false;
    final cur = s.tenant?.currency ?? 'GNF';
    final lowCount = _items.where((p) => p.isLow).length;

    return Scaffold(
      appBar: AppBar(title: const Text('Articles & stock'), actions: const [SyncPill()]),
      floatingActionButton: manage ? FloatingActionButton.extended(onPressed: () => _edit(), icon: const Icon(Icons.add_a_photo_outlined), label: const Text('Ajouter')) : null,
      body: Column(children: [
        Container(color: Colors.white, padding: const EdgeInsets.fromLTRB(16, 4, 16, 10), child: Row(children: [
          Expanded(child: TextField(controller: _q, onChanged: (_) => _load(), decoration: const InputDecoration(hintText: 'Rechercher', prefixIcon: Icon(Icons.search)))),
          const SizedBox(width: 10),
          FilterChip(label: Text(_low ? 'Stock faible' : 'Faible${lowCount > 0 ? ' ($lowCount)' : ''}'), selected: _low, onSelected: (v) { _low = v; _load(); },
              avatar: const Icon(Icons.warning_amber_rounded, size: 18, color: AppColors.orange)),
        ])),
        Expanded(child: _items.isEmpty
            ? EmptyState(icon: Icons.inventory_2_outlined, title: _low ? 'Aucun article en stock faible' : 'Aucun article',
                text: manage ? 'Prenez une photo, indiquez le prix et la quantité : c\'est prêt pour la caisse.' : null,
                action: manage ? FilledButton.icon(onPressed: () => _edit(), icon: const Icon(Icons.add), label: const Text('Premier article')) : null)
            : RefreshIndicator(
                onRefresh: () => s.sync(force: true),
                child: ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
                  itemCount: _items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (_, i) {
                    final p = _items[i];
                    return Card(child: InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: manage ? () => _edit(p) : null,
                      child: Padding(padding: const EdgeInsets.all(10), child: Row(children: [
                        SizedBox(width: 62, height: 62, child: ProductImage(p)),
                        const SizedBox(width: 12),
                        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text(p.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15), maxLines: 1, overflow: TextOverflow.ellipsis),
                          const SizedBox(height: 2),
                          Text(money(p.price, cur), style: TextStyle(color: Theme.of(context).colorScheme.primary, fontWeight: FontWeight.w800)),
                          const SizedBox(height: 4),
                          StockBadge(p),
                        ])),
                        if (manage) IconButton.filledTonal(onPressed: () => _adjust(p), icon: const Icon(Icons.swap_vert), tooltip: 'Entrée / sortie de stock'),
                      ])),
                    ));
                  },
                ),
              )),
      ]),
    );
  }
}
