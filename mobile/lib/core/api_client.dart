import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'config.dart';

class ApiException implements Exception {
  final String message;
  final int? status;
  ApiException(this.message, [this.status]);
  bool get isAuth => status == 401;
  bool get isSubscription => status == 402;
  @override
  String toString() => message;
}

/// Client HTTP de l'API Laravel (jeton Sanctum).
class ApiClient {
  String? token;
  final _timeout = const Duration(seconds: 25);

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Uri _u(String path, [Map<String, String>? q]) => Uri.parse('${AppConfig.apiUrl}/api/v1$path').replace(queryParameters: q);

  Future<Map<String, dynamic>> get(String path, [Map<String, String>? q]) async =>
      _handle(await http.get(_u(path, q), headers: _headers).timeout(_timeout));

  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body) async =>
      _handle(await http.post(_u(path), headers: _headers, body: jsonEncode(body)).timeout(_timeout));

  Future<Map<String, dynamic>> uploadImage(String path, File file) async {
    final req = http.MultipartRequest('POST', _u(path))
      ..headers.addAll({'Accept': 'application/json', if (token != null) 'Authorization': 'Bearer $token'})
      ..files.add(await http.MultipartFile.fromPath('image', file.path));
    final res = await http.Response.fromStream(await req.send().timeout(const Duration(seconds: 60)));
    return _handle(res);
  }

  Map<String, dynamic> _handle(http.Response r) {
    Map<String, dynamic> body = {};
    try {
      final decoded = jsonDecode(utf8.decode(r.bodyBytes));
      if (decoded is Map<String, dynamic>) body = decoded;
    } catch (_) {}
    if (r.statusCode >= 200 && r.statusCode < 300) return body;
    throw ApiException(body['message']?.toString() ?? 'Erreur serveur (${r.statusCode})', r.statusCode);
  }
}
