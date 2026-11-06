import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../widgets/common.dart';

/// "L'œil du propriétaire" : chiffres en direct de toute l'entreprise (toutes caisses, tous vendeurs).
/// Nécessite le réseau ; hors ligne, affiche les ventes de ce téléphone.
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});
  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _data;
  bool _loading = false;
  String? _error;
  int _localTotal = 0, _localCount = 0;

  @override
  void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    final s = context.read<AppState>();
    setState(() { _loading = true; _error = null; });
    final local = await s.db.sales(day: DateTime.now());
    _localTotal = local.fold(0, (a, b) => a + b.total);
    _localCount = local.length;
    try {
      if (s.online) _data = await s.api.get('/dashboard');
    } catch (e) {
      _error = e.toString();
    }
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final cur = s.tenant?.currency ?? 'GNF';
    final d = _data;
    final week = (d?['week'] as List?) ?? [];
    final maxW = week.fold<num>(1, (m, e) => (e['total'] as num) > m ? e['total'] as num : m);
    final team = (d?['team'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(title: const Text('Chiffres'), actions: const [SyncPill()]),
      body: RefreshIndicator(
        onRefresh: () async { await s.sync(force: true); await _load(); },
        child: ListView(padding: const EdgeInsets.all(16), children: [
          Text("Bonjour ${s.user?.name.split(' ').first ?? ''} 👋", style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          Text(dayLabel(DateTime.now()), style: const TextStyle(color: AppColors.muted)),
          const SizedBox(height: 16),
          if (d == null)
            Container(
              padding: const EdgeInsets.all(14), margin: const EdgeInsets.only(bottom: 14),
              decoration: BoxDecoration(color: const Color(0xFFFFF7E6), borderRadius: BorderRadius.circular(14)),
              child: Text(_loading ? 'Chargement des chiffres…' : (s.online ? (_error ?? '') : '📴 Hors ligne : seules les ventes de ce téléphone sont affichées. Tirez vers le bas au retour du réseau.'),
                  style: const TextStyle(color: Color(0xFF8A5800), fontWeight: FontWeight.w600)),
            ),
          GridView.count(
            crossAxisCount: 2, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 1.25,
            children: [
              StatCard(label: "Ventes aujourd'hui", value: money(d?['today_total'] ?? _localTotal, cur), sub: '${d?['today_count'] ?? _localCount} reçu(s)', icon: Icons.payments_outlined, color: Theme.of(context).colorScheme.primary),
              StatCard(label: 'Ce mois-ci', value: d == null ? '—' : money(d['month_total'], cur), icon: Icons.trending_up, color: AppColors.blue),
              StatCard(label: 'Bénéfice du mois', value: d == null ? '—' : money(d['month_profit'], cur), icon: Icons.savings_outlined, color: AppColors.green),
              StatCard(label: 'Stock faible', value: d == null ? '—' : '${d['low_stock']}', sub: 'articles à commander', icon: Icons.warning_amber_rounded, color: AppColors.orange),
            ],
          ),
          if (week.isNotEmpty) ...[
            const SizedBox(height: 16),
            Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('7 derniers jours', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
              const SizedBox(height: 14),
              SizedBox(height: 160, child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: week.map<Widget>((e) {
                final v = (e['total'] as num);
                return Expanded(child: Padding(padding: const EdgeInsets.symmetric(horizontal: 4), child: Column(mainAxisAlignment: MainAxisAlignment.end, children: [
                  Text(v > 0 ? moneyShort(v) : '', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 4),
                  Container(height: (v / maxW * 110).clamp(3, 110).toDouble(), decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.primary, borderRadius: const BorderRadius.vertical(top: Radius.circular(8), bottom: Radius.circular(3)))),
                  const SizedBox(height: 6),
                  Text('${e['day']}', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                ])));
              }).toList())),
            ]))),
          ],
          if (team.isNotEmpty) ...[
            const SizedBox(height: 16),
            Card(child: Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Padding(padding: EdgeInsets.fromLTRB(16, 8, 16, 4), child: Row(children: [
                Icon(Icons.visibility_outlined, size: 18), SizedBox(width: 8),
                Text('Votre équipe aujourd\'hui', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
              ])),
              ...team.map((m) => ListTile(
                    leading: CircleAvatar(backgroundColor: AppColors.pink, child: Text((m['name'] as String).substring(0, 1), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800))),
                    title: Text(m['name'], style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text('${m['role']} · ${m['last_seen'] ?? 'jamais connecté'}${m['active'] == false ? ' · désactivé' : ''}'),
                    trailing: Text(money(m['today_total'], cur), style: const TextStyle(fontWeight: FontWeight.w800)),
                  )),
            ]))),
          ],
        ]),
      ),
    );
  }
}
