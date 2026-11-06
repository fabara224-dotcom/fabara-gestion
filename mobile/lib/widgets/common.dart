import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/theme.dart';
import '../models/models.dart';

/// Pastille "En ligne / Hors ligne / X en attente" affichée en haut des écrans
class SyncPill extends StatelessWidget {
  const SyncPill({super.key});

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final (Color bg, Color fg, IconData icon, String label) = s.syncing
        ? (const Color(0xFFEEF0FF), AppColors.blue, Icons.sync, 'Synchro…')
        : !s.online
            ? (const Color(0xFFFFF3DC), AppColors.orange, Icons.wifi_off_rounded, s.pending > 0 ? 'Hors ligne · ${s.pending}' : 'Hors ligne')
            : s.pending > 0
                ? (const Color(0xFFFFF3DC), AppColors.orange, Icons.cloud_upload_outlined, '${s.pending} en attente')
                : (const Color(0xFFE3F6F3), AppColors.green, Icons.cloud_done_outlined, 'À jour');
    return GestureDetector(
      onTap: () => s.sync(force: true),
      child: Container(
        margin: const EdgeInsets.only(right: 12),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(99)),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 16, color: fg),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(color: fg, fontWeight: FontWeight.w700, fontSize: 12.5)),
        ]),
      ),
    );
  }
}

/// Image d'un article : photo locale (prise hors ligne), photo serveur, ou initiales
class ProductImage extends StatelessWidget {
  final Product product;
  final double radius;
  const ProductImage(this.product, {super.key, this.radius = 12});

  @override
  Widget build(BuildContext context) {
    Widget child;
    if (product.localImage != null && File(product.localImage!).existsSync()) {
      child = Image.file(File(product.localImage!), fit: BoxFit.cover);
    } else if (product.imageUrl != null) {
      child = CachedNetworkImage(imageUrl: product.imageUrl!, fit: BoxFit.cover,
          errorWidget: (_, __, ___) => _initials(), placeholder: (_, __) => Container(color: AppColors.bg));
    } else {
      child = _initials();
    }
    return ClipRRect(borderRadius: BorderRadius.circular(radius), child: Container(color: AppColors.bg, child: child));
  }

  Widget _initials() => Center(
        child: Text(product.name.length >= 2 ? product.name.substring(0, 2).toUpperCase() : product.name.toUpperCase(),
            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 22, color: Color(0xFFC7CCD6))));
}

class StatCard extends StatelessWidget {
  final String label;
  final String value;
  final String? sub;
  final IconData icon;
  final Color color;
  const StatCard({super.key, required this.label, required this.value, this.sub, required this.icon, required this.color});

  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Container(width: 36, height: 36, decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(11)),
                  child: Icon(icon, color: color, size: 19)),
              const Spacer(),
            ]),
            const SizedBox(height: 12),
            Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 12.5, fontWeight: FontWeight.w500)),
            const SizedBox(height: 2),
            FittedBox(fit: BoxFit.scaleDown, alignment: Alignment.centerLeft,
                child: Text(value, style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w800, letterSpacing: -.3))),
            if (sub != null) Text(sub!, style: const TextStyle(color: AppColors.muted, fontSize: 11.5)),
          ]),
        ),
      );
}

class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? text;
  final Widget? action;
  const EmptyState({super.key, required this.icon, required this.title, this.text, this.action});

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            Container(width: 72, height: 72, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(22), border: Border.all(color: AppColors.line)),
                child: Icon(icon, size: 32, color: const Color(0xFF9AA1AE))),
            const SizedBox(height: 16),
            Text(title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700), textAlign: TextAlign.center),
            if (text != null) Padding(padding: const EdgeInsets.only(top: 6), child: Text(text!, style: const TextStyle(color: AppColors.muted), textAlign: TextAlign.center)),
            if (action != null) Padding(padding: const EdgeInsets.only(top: 18), child: action!),
          ]),
        ),
      );
}

class StockBadge extends StatelessWidget {
  final Product p;
  const StockBadge(this.p, {super.key});

  @override
  Widget build(BuildContext context) {
    if (!p.trackStock) return const SizedBox.shrink();
    final (Color bg, Color fg) = p.stock <= 0
        ? (const Color(0xFFFDECEE), AppColors.red)
        : p.isLow
            ? (const Color(0xFFFFF3DC), AppColors.orange)
            : (const Color(0xFFF0F1F4), AppColors.muted);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(99)),
      child: Text('${qty(p.stock)} ${p.unit == 'pièce' ? '' : p.unit}'.trim(), style: TextStyle(color: fg, fontWeight: FontWeight.w700, fontSize: 11.5)),
    );
  }
}

void toast(BuildContext context, String msg, {bool error = false}) {
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(
    content: Text(msg), behavior: SnackBarBehavior.floating,
    backgroundColor: error ? AppColors.red : AppColors.ink,
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
  ));
}
