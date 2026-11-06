import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';
import 'package:uuid/uuid.dart';

import '../core/app_state.dart';
import '../core/theme.dart';
import '../models/models.dart';
import '../widgets/common.dart';

/// Création / modification d'un article avec photo (appareil photo ou galerie). Fonctionne hors ligne.
class ProductFormScreen extends StatefulWidget {
  final Product? product;
  const ProductFormScreen({super.key, this.product});
  @override
  State<ProductFormScreen> createState() => _ProductFormScreenState();
}

class _ProductFormScreenState extends State<ProductFormScreen> {
  final _form = GlobalKey<FormState>();
  late final _name = TextEditingController(text: widget.product?.name);
  late final _price = TextEditingController(text: widget.product?.price.toString());
  late final _cost = TextEditingController(text: (widget.product?.cost ?? 0) == 0 ? '' : widget.product!.cost.toString());
  late final _barcode = TextEditingController(text: widget.product?.barcode);
  late final _stock = TextEditingController();
  late final _alert = TextEditingController(text: (widget.product?.alertThreshold ?? 3).toStringAsFixed(0));
  late String _unit = widget.product?.unit ?? 'pièce';
  late String? _cat = widget.product?.categoryId;
  late bool _track = widget.product?.trackStock ?? true;
  late bool _active = widget.product?.isActive ?? true;
  String? _newImage;
  List<Category> _cats = [];

  bool get _isNew => widget.product == null;

  @override
  void initState() {
    super.initState();
    context.read<AppState>().db.categories().then((c) { if (mounted) setState(() => _cats = c); });
  }

  Future<void> _pick(ImageSource src) async {
    final x = await ImagePicker().pickImage(source: src, maxWidth: 1200, maxHeight: 1200, imageQuality: 78);
    if (x == null) return;
    // copie dans le dossier de l'app pour la garder jusqu'à l'envoi au serveur
    final dir = await getApplicationDocumentsDirectory();
    final dest = p.join(dir.path, 'products', '${const Uuid().v4()}${p.extension(x.path)}');
    await Directory(p.dirname(dest)).create(recursive: true);
    await File(x.path).copy(dest);
    setState(() => _newImage = dest);
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    final s = context.read<AppState>();
    final prod = Product(
      id: widget.product?.id ?? const Uuid().v4(),
      name: _name.text.trim(), categoryId: _cat, barcode: _barcode.text.trim().isEmpty ? null : _barcode.text.trim(),
      price: int.parse(_price.text), cost: int.tryParse(_cost.text) ?? 0, unit: _unit,
      stock: widget.product?.stock ?? 0, alertThreshold: double.tryParse(_alert.text) ?? 0,
      imageUrl: widget.product?.imageUrl, localImage: _newImage ?? widget.product?.localImage,
      trackStock: _track, isActive: _active,
    );
    await s.db.saveProductLocal(prod, imageChanged: _newImage != null);
    final initial = double.tryParse(_stock.text.replaceAll(',', '.'));
    if (_isNew && initial != null && initial > 0) {
      await s.db.addStockMovement(prod, 'in', initial, 'Stock initial');
    }
    await s.refreshPending();
    s.sync();
    if (mounted) { toast(context, _isNew ? 'Article ajouté' : 'Article modifié'); Navigator.pop(context, true); }
  }

  @override
  Widget build(BuildContext context) {
    final preview = _newImage != null
        ? Image.file(File(_newImage!), fit: BoxFit.cover)
        : widget.product != null ? ProductImage(widget.product!, radius: 0) : null;
    final price = int.tryParse(_price.text) ?? 0, cost = int.tryParse(_cost.text) ?? 0;

    return Scaffold(
      appBar: AppBar(title: Text(_isNew ? 'Nouvel article' : 'Modifier l\'article')),
      body: Form(
        key: _form,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          GestureDetector(
            onTap: () => _sourceSheet(),
            child: AspectRatio(aspectRatio: 1.6, child: Container(
              clipBehavior: Clip.antiAlias,
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: AppColors.line, width: 1.5)),
              child: preview ?? const Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                Icon(Icons.add_a_photo_outlined, size: 40, color: AppColors.muted),
                SizedBox(height: 8),
                Text('Prendre une photo de l\'article', style: TextStyle(color: AppColors.muted, fontWeight: FontWeight.w600)),
              ]),
            )),
          ),
          if (preview != null) TextButton.icon(onPressed: _sourceSheet, icon: const Icon(Icons.photo_camera_outlined), label: const Text('Changer la photo')),
          const SizedBox(height: 16),
          TextFormField(controller: _name, textCapitalization: TextCapitalization.sentences,
              decoration: const InputDecoration(labelText: 'Nom de l\'article *'), validator: (v) => (v ?? '').trim().isEmpty ? 'Obligatoire' : null),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(child: TextFormField(controller: _price, keyboardType: TextInputType.number, onChanged: (_) => setState(() {}),
                decoration: const InputDecoration(labelText: 'Prix de vente *'), validator: (v) => int.tryParse(v ?? '') == null ? 'Prix invalide' : null)),
            const SizedBox(width: 12),
            Expanded(child: TextFormField(controller: _cost, keyboardType: TextInputType.number, onChanged: (_) => setState(() {}),
                decoration: const InputDecoration(labelText: 'Prix d\'achat'))),
          ]),
          if (price > 0 && cost > 0) Padding(padding: const EdgeInsets.only(top: 8),
              child: Text('Marge : ${price - cost} par ${_unit} (${((price - cost) / cost * 100).round()} %)', style: const TextStyle(color: AppColors.green, fontWeight: FontWeight.w700))),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(child: DropdownButtonFormField<String?>(
              value: _cats.any((c) => c.id == _cat) ? _cat : null,
              decoration: const InputDecoration(labelText: 'Catégorie'),
              items: [const DropdownMenuItem(value: null, child: Text('Aucune')), ..._cats.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))],
              onChanged: (v) => setState(() => _cat = v),
            )),
            const SizedBox(width: 12),
            SizedBox(width: 130, child: DropdownButtonFormField<String>(
              value: _unit, decoration: const InputDecoration(labelText: 'Unité'),
              items: ['pièce', 'paire', 'kg', 'litre', 'mètre', 'carton', 'paquet', 'sac', 'lot']
                  .map((u) => DropdownMenuItem(value: u, child: Text(u))).toList(),
              onChanged: (v) => setState(() => _unit = v ?? 'pièce'),
            )),
          ]),
          const SizedBox(height: 12),
          TextFormField(controller: _barcode, decoration: const InputDecoration(labelText: 'Code-barres (facultatif)', prefixIcon: Icon(Icons.qr_code_2))),
          const SizedBox(height: 16),
          Card(child: Padding(padding: const EdgeInsets.all(14), child: Column(children: [
            SwitchListTile(contentPadding: EdgeInsets.zero, value: _track, onChanged: (v) => setState(() => _track = v), title: const Text('Suivre le stock')),
            if (_track) Row(children: [
              if (_isNew) ...[
                Expanded(child: TextField(controller: _stock, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Quantité en stock'))),
                const SizedBox(width: 12),
              ],
              Expanded(child: TextField(controller: _alert, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Alerte si moins de'))),
            ]),
            SwitchListTile(contentPadding: EdgeInsets.zero, value: _active, onChanged: (v) => setState(() => _active = v), title: const Text('Visible à la caisse')),
          ]))),
          const SizedBox(height: 20),
          FilledButton.icon(onPressed: _save, icon: const Icon(Icons.check), label: const Text('Enregistrer')),
          const SizedBox(height: 8),
          const Text('Enregistré sur le téléphone, envoyé au serveur dès que le réseau est disponible.', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 12)),
        ]),
      ),
    );
  }

  void _sourceSheet() => showModalBottomSheet(
        context: context, backgroundColor: Colors.white,
        builder: (ctx) => SafeArea(child: Wrap(children: [
          ListTile(leading: const Icon(Icons.photo_camera_outlined), title: const Text('Prendre une photo'), onTap: () { Navigator.pop(ctx); _pick(ImageSource.camera); }),
          ListTile(leading: const Icon(Icons.photo_library_outlined), title: const Text('Choisir dans la galerie'), onTap: () { Navigator.pop(ctx); _pick(ImageSource.gallery); }),
        ])),
      );
}
