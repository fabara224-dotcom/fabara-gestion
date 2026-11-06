import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import 'dashboard_screen.dart';
import 'more_screen.dart';
import 'pos_screen.dart';
import 'products_screen.dart';
import 'sales_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});
  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _i = 0;

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final manage = s.user?.canManage ?? false;

    final tabs = <(Widget, NavigationDestination)>[
      (const PosScreen(), const NavigationDestination(icon: Icon(Icons.point_of_sale_outlined), selectedIcon: Icon(Icons.point_of_sale), label: 'Caisse')),
      (const SalesScreen(), const NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Reçus')),
      (const ProductsScreen(), const NavigationDestination(icon: Icon(Icons.inventory_2_outlined), selectedIcon: Icon(Icons.inventory_2), label: 'Stock')),
      if (manage) (const DashboardScreen(), const NavigationDestination(icon: Icon(Icons.insights_outlined), selectedIcon: Icon(Icons.insights), label: 'Chiffres')),
      (const MoreScreen(), const NavigationDestination(icon: Icon(Icons.more_horiz), label: 'Plus')),
    ];
    if (_i >= tabs.length) _i = 0;

    return Scaffold(
      body: Column(children: [
        if (s.subscriptionBlocked)
          SafeArea(bottom: false, child: MaterialBanner(
            content: const Text('Abonnement expiré : la synchronisation est bloquée. Contactez votre fournisseur.'),
            leading: const Icon(Icons.lock_outline),
            actions: [TextButton(onPressed: () => s.sync(force: true), child: const Text('Réessayer'))],
          )),
        Expanded(child: IndexedStack(index: _i, children: tabs.map((t) => t.$1).toList())),
      ]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _i,
        onDestinationSelected: (i) => setState(() => _i = i),
        destinations: tabs.map((t) => t.$2).toList(),
      ),
    );
  }
}
