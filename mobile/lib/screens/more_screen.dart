import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/config.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../widgets/common.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key});

  Future<void> _expense(BuildContext context) async {
    final s = context.read<AppState>();
    final label = TextEditingController(), amount = TextEditingController();
    String cat = 'divers';
    final ok = await showDialog<bool>(context: context, builder: (ctx) => StatefulBuilder(builder: (ctx, set) => AlertDialog(
          title: const Text('Nouvelle dépense'),
          content: Column(mainAxisSize: MainAxisSize.min, children: [
            TextField(controller: label, decoration: const InputDecoration(labelText: 'Libellé')),
            const SizedBox(height: 10),
            TextField(controller: amount, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Montant')),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(value: cat, decoration: const InputDecoration(labelText: 'Catégorie'), onChanged: (v) => set(() => cat = v ?? 'divers'),
                items: const {'loyer': 'Loyer', 'salaire': 'Salaires', 'transport': 'Transport', 'electricite': 'Électricité / Eau', 'achat': 'Achat marchandises', 'communication': 'Crédit / Internet', 'divers': 'Divers'}
                    .entries.map((e) => DropdownMenuItem(value: e.key, child: Text(e.value))).toList()),
          ]),
          actions: [TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Annuler')), FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Enregistrer'))],
        )));
    final a = int.tryParse(amount.text);
    if (ok == true && a != null && a > 0 && label.text.trim().isNotEmpty) {
      await s.db.addExpense(label.text.trim(), cat, a);
      await s.refreshPending();
      s.sync();
      if (context.mounted) toast(context, 'Dépense enregistrée');
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final t = s.tenant;
    return Scaffold(
      appBar: AppBar(title: const Text('Plus'), actions: const [SyncPill()]),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Card(child: Padding(padding: const EdgeInsets.all(16), child: Row(children: [
          Container(width: 54, height: 54, clipBehavior: Clip.antiAlias, decoration: BoxDecoration(color: Theme.of(context).colorScheme.primary, borderRadius: BorderRadius.circular(15)),
              child: s.logoBytes != null ? Image.memory(s.logoBytes!, fit: BoxFit.cover) : const Icon(Icons.storefront, color: Colors.white)),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(t?.name ?? '', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
            Text('${s.user?.name} · ${s.user?.roleLabel}${s.user?.branch != null ? ' · ${s.user!.branch}' : ''}', style: const TextStyle(color: AppColors.muted, fontSize: 13)),
            if (t?.status == 'trial' && t?.daysLeft != null) Text('Essai gratuit : ${t!.daysLeft} jour(s) restants', style: const TextStyle(color: AppColors.orange, fontSize: 12.5, fontWeight: FontWeight.w700)),
          ])),
        ]))),
        const SizedBox(height: 14),
        Card(child: Column(children: [
          ListTile(
            leading: const Icon(Icons.sync), title: const Text('Synchroniser maintenant'),
            subtitle: Text(s.lastError ?? (s.lastSync != null ? 'Dernière synchro : ${dateTime(s.lastSync!)}' : 'Jamais synchronisé')),
            trailing: s.syncing ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text('${s.pending} en attente'),
            onTap: () => s.sync(force: true),
          ),
          if (s.user?.canManage ?? false) ...[
            const Divider(),
            ListTile(leading: const Icon(Icons.account_balance_wallet_outlined), title: const Text('Ajouter une dépense'), onTap: () => _expense(context)),
          ],
          const Divider(),
          const ListTile(leading: Icon(Icons.language), title: Text('Espace web complet'), subtitle: Text('Rapports détaillés, équipe, logo, abonnement : ${AppConfig.apiUrl}')),
        ])),
        const SizedBox(height: 14),
        Card(child: ListTile(
          leading: const Icon(Icons.logout, color: AppColors.red), title: const Text('Se déconnecter', style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w700)),
          onTap: () async {
            final ok = await s.logout();
            if (!ok && context.mounted) {
              final force = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
                    title: const Text('Ventes non envoyées'),
                    content: Text('${s.pending} élément(s) ne sont pas encore sur le serveur. Connectez-vous à internet et synchronisez avant de vous déconnecter, sinon ils seront perdus.'),
                    actions: [
                      TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
                      TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Déconnecter quand même', style: TextStyle(color: AppColors.red))),
                    ],
                  ));
              if (force == true) await s.logout(force: true);
            }
          },
        )),
        const SizedBox(height: 20),
        const Center(child: Text('${AppConfig.appName} · v1.0', style: TextStyle(color: AppColors.muted, fontSize: 12))),
      ]),
    );
  }
}
