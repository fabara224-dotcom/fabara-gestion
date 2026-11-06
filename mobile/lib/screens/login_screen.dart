import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/app_state.dart';
import '../core/config.dart';
import '../core/theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _pass = TextEditingController();
  bool _busy = false, _hide = true;
  String? _error;

  Future<void> _submit() async {
    setState(() { _busy = true; _error = null; });
    try {
      await context.read<AppState>().login(_email.text, _pass.text);
    } catch (e) {
      setState(() => _error = e.toString().contains('Failed host lookup') || e.toString().contains('SocketException')
          ? 'Pas de connexion internet. La première connexion nécessite le réseau.'
          : e.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  for (final c in [AppColors.red, AppColors.yellow, AppColors.green, AppColors.pink])
                    Container(width: 12, height: 44, margin: const EdgeInsets.symmetric(horizontal: 3), decoration: BoxDecoration(color: c, borderRadius: BorderRadius.circular(5))),
                ]),
                const SizedBox(height: 20),
                const Text(AppConfig.appName, textAlign: TextAlign.center, style: TextStyle(fontSize: 28, fontWeight: FontWeight.w900, letterSpacing: -.5)),
                const SizedBox(height: 6),
                const Text('Caisse, stock et reçus — même sans internet', textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 15)),
                const SizedBox(height: 36),
                TextField(controller: _email, keyboardType: TextInputType.emailAddress, autofillHints: const [AutofillHints.email],
                    decoration: const InputDecoration(labelText: 'E-mail', prefixIcon: Icon(Icons.mail_outline))),
                const SizedBox(height: 14),
                TextField(controller: _pass, obscureText: _hide, onSubmitted: (_) => _submit(),
                    decoration: InputDecoration(labelText: 'Mot de passe', prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(icon: Icon(_hide ? Icons.visibility_outlined : Icons.visibility_off_outlined), onPressed: () => setState(() => _hide = !_hide)))),
                if (_error != null) Padding(padding: const EdgeInsets.only(top: 14), child: Text(_error!, style: const TextStyle(color: AppColors.red, fontWeight: FontWeight.w600))),
                const SizedBox(height: 24),
                FilledButton(onPressed: _busy ? null : _submit,
                    child: _busy ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : const Text('Se connecter')),
                const SizedBox(height: 20),
                const Text('Votre compte est créé par le propriétaire de votre boutique.\nPas encore client ? Inscrivez votre commerce sur le site.',
                    textAlign: TextAlign.center, style: TextStyle(color: AppColors.muted, fontSize: 13)),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
