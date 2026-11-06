import 'package:flutter/material.dart';

class AppColors {
  static const red = Color(0xFFD7263D);
  static const yellow = Color(0xFFF4B400);
  static const green = Color(0xFF1B998B);
  static const pink = Color(0xFFE84A8A);
  static const blue = Color(0xFF4A5BD4);
  static const orange = Color(0xFFC27C00);
  static const ink = Color(0xFF151A26);
  static const muted = Color(0xFF6B7280);
  static const line = Color(0xFFECEEF3);
  static const bg = Color(0xFFF7F8FB);
}

Color parseHex(String? hex, [Color fallback = AppColors.red]) {
  if (hex == null || !RegExp(r'^#?[0-9a-fA-F]{6}$').hasMatch(hex)) return fallback;
  return Color(int.parse('FF${hex.replaceAll('#', '')}', radix: 16));
}

/// Thème clair, fond blanc, aux couleurs de l'entreprise connectée.
ThemeData buildTheme(Color brand) {
  final scheme = ColorScheme.fromSeed(seedColor: brand, primary: brand, surface: Colors.white, brightness: Brightness.light);
  final radius = BorderRadius.circular(14);
  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: AppColors.bg,
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.white, foregroundColor: AppColors.ink, elevation: 0, scrolledUnderElevation: 0.5,
      centerTitle: false, titleTextStyle: TextStyle(color: AppColors.ink, fontSize: 20, fontWeight: FontWeight.w800),
    ),
    cardTheme: CardThemeData(
      color: Colors.white, elevation: 0, margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18), side: const BorderSide(color: AppColors.line)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true, fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(borderRadius: radius, borderSide: const BorderSide(color: Color(0xFFE2E5EC), width: 1.5)),
      enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: const BorderSide(color: Color(0xFFE2E5EC), width: 1.5)),
      focusedBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: brand, width: 2)),
    ),
    filledButtonTheme: FilledButtonThemeData(style: FilledButton.styleFrom(
      minimumSize: const Size(48, 54), shape: RoundedRectangleBorder(borderRadius: radius),
      textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16),
    )),
    outlinedButtonTheme: OutlinedButtonThemeData(style: OutlinedButton.styleFrom(
      minimumSize: const Size(48, 50), shape: RoundedRectangleBorder(borderRadius: radius),
      side: const BorderSide(color: Color(0xFFE2E5EC), width: 1.5), foregroundColor: AppColors.ink,
      textStyle: const TextStyle(fontWeight: FontWeight.w600),
    )),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white, indicatorColor: brand.withValues(alpha: .12), height: 68,
      labelTextStyle: WidgetStateProperty.all(const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
    ),
    chipTheme: ChipThemeData(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(99)), side: const BorderSide(color: Color(0xFFE2E5EC))),
    dividerTheme: const DividerThemeData(color: AppColors.line, space: 1),
  );
}
