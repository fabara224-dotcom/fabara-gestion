import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../widgets/common.dart';

/// Panier + encaissement. Renvoie la vente enregistrée (localement) à la fermeture.
class CheckoutSheet extends StatefulWidget {
  final Map<String, CartLine> cart;
  final VoidCallback onChanged;
  const CheckoutSheet({super.key, required this.cart, required this.onChanged});
  @override
  State<CheckoutSheet> createState() => _CheckoutSheetState();
}

class _CheckoutSheetState extends State<CheckoutSheet> {
  String _method = 'cash';
  final _paid = TextEditingController();
  final _discount = TextEditingController();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  bool _busy = false;

  int get _subtotal => widget.cart.values.fold(0, (s, l) => s + l.total);
  int get _disc => (int.tryParse(_discount.text) ?? 0).clamp(0, _subtotal).toInt();
  int get _total => _subtotal - _disc;
  int get _paidValue => _paid.text.isEmpty ? (_method == 'credit' ? 0 : _total) : (int.tryParse(_paid.text) ?? 0);

  void _qty(CartLine l, double delta) {
    setState(() {
      l.quantity += delta;
      if (l.quantity <= 0) widget.cart.remove(l.product.id);
    });
    widget.onChanged();
    if (widget.cart.isEmpty) Navigator.pop(context);
  }

  Future<void> _confirm() async {
    if (_method == 'credit' && _name.text.trim().isEmpty && _phone.text.trim().isEmpty) {
      toast(context, 'Pour une vente à crédit, indiquez le nom ou le téléphone du client.', error: true);
      return;
    }
    if (_method != 'credit' && _paidValue < _total) {
      toast(context, 'Le montant reçu est inférieur au total. Choisissez « À crédit » s\'il reste un montant dû.', error: true);
      return;
    }
    setState(() => _busy = true);
    final s = context.read<AppState>();
    final sale = await s.db.createSale(
      lines: widget.cart.values.toList(), number: await s.nextLocalNumber(), paymentMethod: _method, paid: _paidValue,
      discount: _disc, customerName: _name.text.trim().isEmpty ? null : _name.text.trim(),
      customerPhone: _phone.text.trim().isEmpty ? null : _phone.text.trim(), seller: s.user?.name,
    );
    await s.refreshPending();
    s.sync(); // envoi en arrière-plan si le réseau est là
    if (mounted) Navigator.pop(context, sale);
  }

  @override
  Widget build(BuildContext context) {
    final cur = context.read<AppState>().tenant?.currency ?? 'GNF';
    final brand = Theme.of(context).colorScheme.primary;
    final change = _paidValue - _total;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: DraggableScrollableSheet(
        expand: false, initialChildSize: .92, maxChildSize: .96, minChildSize: .5,
        builder: (_, scroll) => ListView(controller: scroll, padding: const EdgeInsets.fromLTRB(20, 10, 20, 24), children: [
          Center(child: Container(width: 44, height: 5, decoration: BoxDecoration(color: AppColors.line, borderRadius: BorderRadius.circular(9)))),
          const SizedBox(height: 14),
          const Text('Panier', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          ...widget.cart.values.map((l) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(children: [
                  SizedBox(width: 48, height: 48, child: ProductImage(l.product)),
                  const SizedBox(width: 12),
                  Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(l.product.name, style: const TextStyle(fontWeight: FontWeight.w700), maxLines: 1, overflow: TextOverflow.ellipsis),
                    Text(money(l.product.price, cur), style: const TextStyle(color: AppColors.muted, fontSize: 12.5)),
                  ])),
                  _QtyBox(value: l.quantity, onMinus: () => _qty(l, -1), onPlus: () => _qty(l, 1)),
                  SizedBox(width: 100, child: Text(money(l.total, cur), textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.w800))),
                ]),
              )),
          const Divider(height: 28),
          Row(children: [
            const Text('Remise', style: TextStyle(color: AppColors.muted)),
            const Spacer(),
            SizedBox(width: 150, child: TextField(controller: _discount, keyboardType: TextInputType.number, textAlign: TextAlign.right,
                onChanged: (_) => setState(() {}), decoration: const InputDecoration(hintText: '0', isDense: true))),
          ]),
          const SizedBox(height: 14),
          Row(children: [
            const Text('Total', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
            const Spacer(),
            Text(money(_total, cur), style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: brand, letterSpacing: -.5)),
          ]),
          const SizedBox(height: 18),
          const Text('Paiement', style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          Wrap(spacing: 8, runSpacing: 8, children: paymentMethods.entries.map((e) => ChoiceChip(
                label: Text(e.value), selected: _method == e.key, showCheckmark: false,
                onSelected: (_) => setState(() { _method = e.key; _paid.clear(); }),
                selectedColor: brand.withValues(alpha: .12),
                side: BorderSide(color: _method == e.key ? brand : AppColors.line, width: 1.5),
                labelStyle: TextStyle(fontWeight: FontWeight.w700, color: _method == e.key ? brand : AppColors.ink),
              )).toList()),
          const SizedBox(height: 14),
          TextField(controller: _paid, keyboardType: TextInputType.number, onChanged: (_) => setState(() {}),
              decoration: InputDecoration(labelText: _method == 'credit' ? 'Avance versée (facultatif)' : 'Montant reçu du client', hintText: '$_total')),
          if (_method != 'credit' && change > 0)
            _info('Monnaie à rendre', money(change, cur), AppColors.green),
          if (_method == 'credit' || change < 0)
            _info('Reste dû', money(_total - _paidValue, cur), AppColors.red),
          const SizedBox(height: 14),
          ExpansionTile(
            tilePadding: EdgeInsets.zero, initiallyExpanded: _method == 'credit',
            title: const Text('Client (pour envoyer le reçu)', style: TextStyle(fontWeight: FontWeight.w700)),
            children: [
              TextField(controller: _name, decoration: const InputDecoration(labelText: 'Nom', prefixIcon: Icon(Icons.person_outline))),
              const SizedBox(height: 10),
              TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Téléphone / WhatsApp', prefixIcon: Icon(Icons.phone_outlined))),
              const SizedBox(height: 8),
            ],
          ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _busy ? null : _confirm,
            style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(60)),
            icon: const Icon(Icons.check_circle_outline),
            label: Text('Encaisser ${money(_total, cur)}', style: const TextStyle(fontSize: 17)),
          ),
          if (!context.watch<AppState>().online)
            const Padding(padding: EdgeInsets.only(top: 10), child: Text('📴 Hors ligne : la vente est enregistrée sur le téléphone et sera envoyée automatiquement.',
                textAlign: TextAlign.center, style: TextStyle(color: AppColors.orange, fontSize: 12.5, fontWeight: FontWeight.w600))),
        ]),
      ),
    );
  }

  Widget _info(String label, String value, Color color) => Container(
        margin: const EdgeInsets.only(top: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(color: color.withValues(alpha: .08), borderRadius: BorderRadius.circular(12)),
        child: Row(children: [Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w600)), const Spacer(),
          Text(value, style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 16))]),
      );
}

class _QtyBox extends StatelessWidget {
  final double value;
  final VoidCallback onMinus, onPlus;
  const _QtyBox({required this.value, required this.onMinus, required this.onPlus});

  @override
  Widget build(BuildContext context) => Container(
        decoration: BoxDecoration(border: Border.all(color: const Color(0xFFE2E5EC), width: 1.5), borderRadius: BorderRadius.circular(12)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          IconButton(visualDensity: VisualDensity.compact, icon: const Icon(Icons.remove, size: 18), onPressed: onMinus),
          Text(qty(value), style: const TextStyle(fontWeight: FontWeight.w800)),
          IconButton(visualDensity: VisualDensity.compact, icon: const Icon(Icons.add, size: 18), onPressed: onPlus),
        ]),
      );
}
