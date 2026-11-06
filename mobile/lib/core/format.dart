import 'package:intl/intl.dart';

final _money = NumberFormat.decimalPattern('fr_FR');

/// 1250000 → "1 250 000 GNF"
String money(num? v, [String currency = 'GNF']) =>
    '${_money.format((v ?? 0).round()).replaceAll(' ', ' ').replaceAll(' ', ' ')} $currency';

/// 1250000 → "1,25 M"
String moneyShort(num? v) {
  final a = (v ?? 0).toDouble();
  if (a >= 1e9) return '${(a / 1e9).toStringAsFixed(2).replaceAll('.', ',')} Md';
  if (a >= 1e6) return '${(a / 1e6).toStringAsFixed(2).replaceAll('.', ',')} M';
  if (a >= 1e3) return '${(a / 1e3).toStringAsFixed(0)} k';
  return a.toStringAsFixed(0);
}

String qty(num? q) {
  final v = (q ?? 0).toDouble();
  return v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toStringAsFixed(2).replaceAll('.', ',');
}

String dateTime(DateTime d) => DateFormat('dd/MM/yyyy HH:mm', 'fr_FR').format(d.toLocal());
String timeOnly(DateTime d) => DateFormat('HH:mm', 'fr_FR').format(d.toLocal());
String dayLabel(DateTime d) => DateFormat('EEEE d MMMM', 'fr_FR').format(d.toLocal());

const paymentMethods = <String, String>{
  'cash': 'Espèces',
  'orange_money': 'Orange Money',
  'mtn_momo': 'MTN MoMo',
  'card': 'Carte',
  'credit': 'À crédit',
};
