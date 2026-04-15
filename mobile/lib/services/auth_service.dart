import 'dart:convert';
import '../models/user.dart';
import 'api_service.dart';

class AuthService {
  static Future<({User user, String token})> register(
      String name, String email, String password, String passwordConfirmation) async {
    final res = await ApiService.post('/register', body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });

    if (res.statusCode != 201) {
      final body = jsonDecode(res.body);
      throw Exception(body['message'] ?? 'Erreur d\'inscription');
    }

    final body = jsonDecode(res.body);
    return (
      user: User.fromJson(body['user']),
      token: body['token'] as String,
    );
  }

  static Future<({User user, String token})> login(
      String email, String password) async {
    final res = await ApiService.post('/login', body: {
      'email': email,
      'password': password,
    });

    if (res.statusCode != 200) {
      final body = jsonDecode(res.body);
      throw Exception(body['message'] ?? 'Identifiants invalides');
    }

    final body = jsonDecode(res.body);
    return (
      user: User.fromJson(body['user']),
      token: body['token'] as String,
    );
  }

  static Future<void> logout() async {
    await ApiService.post('/logout');
    await ApiService.deleteToken();
  }

  static Future<User?> fetchUser() async {
    final res = await ApiService.get('/user');
    if (res.statusCode != 200) return null;
    return User.fromJson(jsonDecode(res.body));
  }
}
