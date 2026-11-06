import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../services/receipt_pdf.dart';
import '../widgets/common.dart';

/// Reçu à remettre à l'acheteur : imprimer, partager en PDF, envoyer le lien.
class ReceiptScreen extends StatefulWidget {
  final Sale sale;
  final bool justSold;
  const ReceiptScreen({super.key, required this.sale, this.justSold = false});
  @override
  State<ReceiptScreen> createState() => _ReceiptScreenState();
}

class _ReceiptScreenState extends State<ReceiptScreen> {
  late Sale sale = widget.sale;
  DateTime? _seen;

  Future<void> _reload() async {
    final list = await context.read<AppState>().db.sales(limit: 500);
    final fresh = list.where((x) => x.id == sale.id);
    if (fresh.isNotEmpty && mounted) setState(() => sale = fresh.first);
  }

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    if (s.lastSync != _seen) { _seen = s.lastSync; WidgetsBinding.instance.addPostFrameCallback((_) => _reload()); }
    final t = s.tenant!;
    final brand = Theme.of(context).colorScheme.primary;
    final cur = t.currency;

    return Scaffold(
      appBar: AppBar(title: Text('Reçu ${sale.number}'), actions: const [SyncPill()]),
      body: ListView(padding: const EdgeInsets.fromLTRB(16, 16, 16, 30), children: [
        if (widget.justSold)
          Container(
            padding: const EdgeInsets.all(14), margin: const EdgeInsets.only(bottom: 16),
            decoration: BoxDecoration(color: const Color(0xFFE3F6F3), borderRadius: BorderRadius.circular(14)),
            child: Row(children: [
              const Icon(Icons.check_circle, color: AppColors.green),
              const SizedBox(width: 10),
              Expanded(child: Text(sale.change > 0 ? 'Vente enregistrée. Monnaie à rendre : ${money(sale.change, cur)}' : 'Vente enregistrée.',
                  style: const TextStyle(color: AppColors.green, fontWeight: FontWeight.w700))),
            ]),
          ),

        // ---- Le reçu ----
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20),
              boxShadow: const [BoxShadow(color: Color(0x1A101828), blurRadius: 30, offset: Offset(0, 12))]),
          child: Column(children: [
            Container(width: 60, height: 60, decoration: BoxDecoration(color: brand, borderRadius: BorderRadius.circular(16)), clipBehavior: Clip.antiAlias,
                child: s.logoBytes != null ? Image.memory(s.logoBytes!, fit: BoxFit.cover)
                    : Center(child: Text(t.name.isNotEmpty ? t.name.substring(0, 1).toUpperCase() : '?', style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800)))),
            const SizedBox(height: 10),
            Text(t.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            if ((t.receiptHeader ?? '').isNotEmpty) Text(t.receiptHeader!, style: const TextStyle(color: AppColors.muted, fontSize: 13)),
            if ((t.phone ?? '').isNotEmpty) Text('Tél : ${t.phone}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
            const _Dash(),
            _row('Reçu', sale.number, bold: true),
            _row('Date', dateTime(sale.soldAt)),
            if (sale.seller != null) _row('Vendeur', sale.seller!),
            if ((sale.customerName ?? '').isNotEmpty) _row('Client', sale.customerName!),
            const _Dash(),
            ...sale.items.map((i) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 5),
                  child: Row(children: [
                    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(i.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text('${qty(i.quantity)} × ${money(i.unitPrice, cur)}', style: const TextStyle(color: AppColors.muted, fontSize: 12.5)),
                    ])),
                    Text(money(i.total, cur), style: const TextStyle(fontWeight: FontWeight.w700)),
                  ]),
                )),
            const _Dash(),
            if (sale.discount > 0) _row('Remise', '− ${money(sale.discount, cur)}'),
            Row(children: [
              const Text('TOTAL', style: TextStyle(fontSize: 19, fontWeight: FontWeight.w900)),
              const Spacer(),
              Text(money(sale.total, cur), style: TextStyle(fontSize: 21, fontWeight: FontWeight.w900, color: brand)),
            ]),
            const SizedBox(height: 6),
            _row('Payé (${paymentMethods[sale.paymentMethod] ?? sale.paymentMethod})', money(sale.paid, cur)),
            if (sale.change > 0) _row('Monnaie rendue', money(sale.change, cur)),
            if (sale.due > 0) _row('Reste à payer', money(sale.due, cur), bold: true, color: AppColors.red),
            const SizedBox(height: 14),
            Text(t.receiptFooter?.isNotEmpty == true ? t.receiptFooter! : 'Merci pour votre achat !', style: const TextStyle(color: AppColors.muted)),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(color: sale.synced ? const Color(0xFFE3F6F3) : const Color(0xFFFFF3DC), borderRadius: BorderRadius.circular(99)),
              child: Text(sale.synced ? '✓ Enregistré sur le serveur' : '⏳ En attente de connexion',
                  style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: sale.synced ? AppColors.green : AppColors.orange)),
            ),
          ]),
        ),

        const SizedBox(height: 22),
        const Text('Donner le reçu à l\'acheteur', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        const SizedBox(height: 10),
        FilledButton.icon(
          onPressed: () => ReceiptPdf.printSale(sale, t, s.logoBytes),
          icon: const Icon(Icons.print_outlined), label: const Text('Imprimer le ticket'),
          style: FilledButton.styleFrom(backgroundColor: AppColors.ink, minimumSize: const Size.fromHeight(56)),
        ),
        const SizedBox(height: 10),
        FilledButton.icon(
          onPressed: () => ReceiptPdf.sharePdf(sale, t, s.logoBytes),
          icon: const Icon(Icons.picture_as_pdf_outlined), label: const Text('Envoyer le reçu PDF (WhatsApp…)'),
          style: FilledButton.styleFrom(backgroundColor: const Color(0xFF25D366), minimumSize: const Size.fromHeight(56)),
        ),
        const SizedBox(height: 10),
        if (sale.receiptUrl != null)
          OutlinedButton.icon(
            onPressed: () async {
              await Clipboard.setData(ClipboardData(text: sale.receiptUrl!));
              if (context.mounted) toast(context, 'Lien du reçu copié');
              await Share.share('Votre reçu ${sale.number} — ${t.name} : ${sale.receiptUrl}');
            },
            icon: const Icon(Icons.link), label: const Text('Partager le lien du reçu en ligne'),
          )
        else
          const Text('Le lien en ligne du reçu sera disponible après la synchronisation. Le PDF fonctionne déjà sans internet.',
              style: TextStyle(color: AppColors.muted, fontSize: 12.5), textAlign: TextAlign.center),
        if (widget.justSold) ...[
          const SizedBox(height: 18),
          TextButton.icon(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.add_shopping_cart), label: const Text('Nouvelle vente')),
        ],
      ]),
    );
  }

  Widget _row(String a, String b, {bool bold = false, Color? color}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2.5),
        child: Row(children: [
          Text(a, style: TextStyle(color: color ?? AppColors.muted, fontWeight: bold ? FontWeight.w700 : null)),
          const Spacer(),
          Flexible(child: Text(b, textAlign: TextAlign.right, style: TextStyle(fontWeight: bold ? FontWeight.w800 : FontWeight.w500, color: color))),
        ]),
      );
}

class _Dash extends StatelessWidget {
  const _Dash();
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: LayoutBuilder(builder: (_, c) => Row(children: List.generate((c.maxWidth / 8).floor(),
            (_) => Expanded(child: Container(height: 1.4, margin: const EdgeInsets.symmetric(horizontal: 2), color: const Color(0xFFDDE0E6)))))),
      );
}
