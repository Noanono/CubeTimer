import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config.dart';

class ApiService {
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'auth_token';

  static Future<String?> getToken() => _storage.read(key: _tokenKey);
  static Future<void> setToken(String token) =>
      _storage.write(key: _tokenKey, value: token);
  static Future<void> deleteToken() => _storage.delete(key: _tokenKey);

  static Map<String, String> _headers(String? token) => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  static Future<http.Response> get(String path,
      {Map<String, String>? queryParams}) async {
    final token = await getToken();
    final uri = Uri.parse('${AppConfig.apiBaseUrl}$path')
        .replace(queryParameters: queryParams);
    return http.get(uri, headers: _headers(token));
  }

  static Future<http.Response> post(String path,
      {Map<String, dynamic>? body}) async {
    final token = await getToken();
    return http.post(
      Uri.parse('${AppConfig.apiBaseUrl}$path'),
      headers: _headers(token),
      body: body != null ? jsonEncode(body) : null,
    );
  }

  static Future<http.Response> patch(String path,
      {Map<String, dynamic>? body}) async {
    final token = await getToken();
    return http.patch(
      Uri.parse('${AppConfig.apiBaseUrl}$path'),
      headers: _headers(token),
      body: body != null ? jsonEncode(body) : null,
    );
  }

  static Future<http.Response> delete(String path) async {
    final token = await getToken();
    return http.delete(
      Uri.parse('${AppConfig.apiBaseUrl}$path'),
      headers: _headers(token),
    );
  }
}
