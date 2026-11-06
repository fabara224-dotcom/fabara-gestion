import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../widgets/common.dart';
import 'receipt_screen.dart';

/// Historique des ventes faites sur ce téléphone, jour par jour.
class SalesScreen extends StatefulWidget {
  const SalesScreen({super.key});
  @override
  State<SalesScreen> createState() => _SalesScreenState();
}

class _SalesScreenState extends State<SalesScreen> {
  DateTime _day = DateTime.now();
  List<Sale> _sales = [];
  DateTime? _seen;
  int _pendingSeen = -1;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    final list = await context.read<AppState>().db.sales(day: _day);
    if (mounted) setState(() => _sales = list);
  }

  bool get _isToday { final n = DateTime.now(); return _day.year == n.year && _day.month == n.month && _day.day == n.day; }

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    if (s.lastSync != _seen || s.pending != _pendingSeen) {
      _seen = s.lastSync; _pendingSeen = s.pending;
      WidgetsBinding.instance.addPostFrameCallback((_) => _load());
    }
    final cur = s.tenant?.currency ?? 'GNF';
    final total = _sales.fold<int>(0, (a, b) => a + b.total);

    return Scaffold(
      appBar: AppBar(title: const Text('Reçus'), actions: const [SyncPill()]),
      body: Column(children: [
        Container(
          color: Colors.white, padding: const EdgeInsets.fromLTRB(8, 0, 8, 12),
          child: Row(children: [
            IconButton(onPressed: () { _day = _day.subtract(const Duration(days: 1)); _load(); }, icon: const Icon(Icons.chevron_left)),
            Expanded(child: InkWell(
              onTap: () async {
                final d = await showDatePicker(context: context, initialDate: _day, firstDate: DateTime(2024), lastDate: DateTime.now());
                if (d != null) { _day = d; _load(); }
              },
              child: Column(children: [
                Text(_isToday ? "Aujourd'hui" : dayLabel(_day), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                Text('${_sales.length} reçu(s) · ${money(total, cur)}', style: const TextStyle(color: AppColors.muted, fontSize: 13)),
              ]),
            )),
            IconButton(onPressed: _isToday ? null : () { _day = _day.add(const Duration(days: 1)); _load(); }, icon: const Icon(Icons.chevron_right)),
          ]),
        ),
        Expanded(child: _sales.isEmpty
            ? const EmptyState(icon: Icons.receipt_long_outlined, title: 'Aucune vente ce jour-là')
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: _sales.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final sale = _sales[i];
                  return Card(child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ReceiptScreen(sale: sale))),
                    leading: CircleAvatar(
                      backgroundColor: sale.synced ? const Color(0xFFE3F6F3) : const Color(0xFFFFF3DC),
                      child: Icon(sale.synced ? Icons.cloud_done_outlined : Icons.cloud_upload_outlined, color: sale.synced ? AppColors.green : AppColors.orange, size: 20),
                    ),
                    title: Text(sale.number, style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text('${timeOnly(sale.soldAt)} · ${paymentMethods[sale.paymentMethod] ?? sale.paymentMethod}'
                        '${(sale.customerName ?? '').isNotEmpty ? ' · ${sale.customerName}' : ''}'),
                    trailing: Text(money(sale.total, cur), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                  ));
                },
              )),
      ]),
    );
  }
}
