import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'core/app_state.dart';
import 'core/config.dart';
import 'core/theme.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('fr_FR');
  final state = AppState();
  await state.init();
  runApp(ChangeNotifierProvider.value(value: state, child: const FabaraApp()));
}

class FabaraApp extends StatelessWidget {
  const FabaraApp({super.key});

  @override
  Widget build(BuildContext context) {
    final s = context.watch<AppState>();
    final brand = parseHex(s.tenant?.brandColor);
    return MaterialApp(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      theme: buildTheme(brand),
      locale: const Locale('fr', 'FR'),
      supportedLocales: const [Locale('fr', 'FR')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: !s.ready
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : (s.loggedIn ? const HomeShell() : const LoginScreen()),
    );
  }
}
