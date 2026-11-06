import 'dart:typed_data';

import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';
import 'package:share_plus/share_plus.dart';

import '../core/format.dart';
import '../models/models.dart';

/// Génère le reçu à remettre à l'acheteur, SANS internet :
/// ticket 80 mm (imprimante Bluetooth/USB via le système) ou PDF à partager (WhatsApp, etc.).
class ReceiptPdf {
  static Future<Uint8List> build(Sale sale, TenantInfo t, {Uint8List? logo, bool ticket = true}) async {
    final doc = pw.Document(title: 'Reçu ${sale.number}', author: t.name);
    final cur = t.currency;
    final base = ticket ? 8.5 : 10.5;
    final dash = pw.Container(margin: const pw.EdgeInsets.symmetric(vertical: 5),
        decoration: const pw.BoxDecoration(border: pw.Border(top: pw.BorderSide(width: .6, style: pw.BorderStyle.dashed))));

    pw.Widget row(String a, String b, {bool bold = false, double? size}) => pw.Row(
          mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
          children: [
            pw.Text(a, style: pw.TextStyle(fontSize: size ?? base, fontWeight: bold ? pw.FontWeight.bold : null)),
            pw.Text(b, style: pw.TextStyle(fontSize: size ?? base, fontWeight: bold ? pw.FontWeight.bold : null)),
          ],
        );

    final format = ticket ? PdfPageFormat.roll80.copyWith(marginLeft: 8, marginRight: 8, marginTop: 8, marginBottom: 12) : PdfPageFormat.a5;

    final children = <pw.Widget>[
          if (logo != null) pw.Center(child: pw.Image(pw.MemoryImage(logo), width: 54, height: 54, fit: pw.BoxFit.contain)),
          pw.SizedBox(height: 4),
          pw.Text(t.name, textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base + 4, fontWeight: pw.FontWeight.bold)),
          if ((t.receiptHeader ?? '').isNotEmpty) pw.Text(t.receiptHeader!, textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base)),
          if ((t.address ?? '').isNotEmpty || (t.city ?? '').isNotEmpty)
            pw.Text('${t.address ?? ''} ${t.city ?? ''}'.trim(), textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base - .5)),
          if ((t.phone ?? '').isNotEmpty) pw.Text('Tél : ${t.phone}', textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base - .5)),
          if ((t.taxId ?? '').isNotEmpty) pw.Text('NIF/RCCM : ${t.taxId}', textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base - .5)),
          dash,
          row('Reçu', sale.number, bold: true),
          row('Date', dateTime(sale.soldAt)),
          if (sale.seller != null) row('Vendeur', sale.seller!),
          if ((sale.customerName ?? '').isNotEmpty) row('Client', sale.customerName!),
          dash,
          ...sale.items.expand((i) => [
                pw.Text(i.name, style: pw.TextStyle(fontSize: base, fontWeight: pw.FontWeight.bold)),
                row('  ${qty(i.quantity)} x ${money(i.unitPrice, cur)}', money(i.total, cur)),
                pw.SizedBox(height: 2),
              ]),
          dash,
          if (sale.discount > 0) row('Remise', '- ${money(sale.discount, cur)}'),
          row('TOTAL', money(sale.total, cur), bold: true, size: base + 4),
          pw.SizedBox(height: 3),
          row('Payé (${paymentMethods[sale.paymentMethod] ?? sale.paymentMethod})', money(sale.paid, cur)),
          if (sale.change > 0) row('Monnaie rendue', money(sale.change, cur)),
          if (sale.due > 0) row('Reste à payer', money(sale.due, cur), bold: true),
          dash,
          pw.Text(t.receiptFooter?.isNotEmpty == true ? t.receiptFooter! : 'Merci pour votre achat !',
              textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base)),
          if (sale.receiptUrl != null) ...[
            pw.SizedBox(height: 6),
            pw.Center(child: pw.BarcodeWidget(barcode: pw.Barcode.qrCode(), data: sale.receiptUrl!, width: 64, height: 64)),
            pw.Text('Scannez pour vérifier ce reçu', textAlign: pw.TextAlign.center, style: pw.TextStyle(fontSize: base - 2)),
          ],
    ];

    if (ticket) {
      // rouleau 80 mm : hauteur illimitée, une seule "page"
      doc.addPage(pw.Page(pageFormat: format, build: (_) => pw.Column(crossAxisAlignment: pw.CrossAxisAlignment.stretch, children: children)));
    } else {
      doc.addPage(pw.MultiPage(pageFormat: format, crossAxisAlignment: pw.CrossAxisAlignment.stretch, build: (_) => children));
    }
    return doc.save();
  }

  /// Ouvre la fenêtre d'impression (imprimante thermique Bluetooth, Wi-Fi, ou "Enregistrer en PDF")
  static Future<void> printSale(Sale sale, TenantInfo t, Uint8List? logo) async {
    await Printing.layoutPdf(name: 'Reçu ${sale.number}', format: PdfPageFormat.roll80,
        onLayout: (_) => build(sale, t, logo: logo, ticket: true));
  }

  /// Partage le reçu en PDF (WhatsApp, Telegram, e-mail…)
  static Future<void> sharePdf(Sale sale, TenantInfo t, Uint8List? logo) async {
    final bytes = await build(sale, t, logo: logo, ticket: false);
    await Share.shareXFiles(
      [XFile.fromData(bytes, mimeType: 'application/pdf', name: 'recu-${sale.number}.pdf')],
      text: 'Votre reçu ${sale.number} — ${t.name}${sale.receiptUrl != null ? '\n${sale.receiptUrl}' : ''}',
    );
  }
}
