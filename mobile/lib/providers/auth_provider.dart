import 'package:flutter/material.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  User? _user;
  bool _loading = true;

  User? get user => _user;
  bool get isLoggedIn => _user != null;
  bool get loading => _loading;

  Future<void> tryAutoLogin() async {
    _loading = true;
    notifyListeners();

    final token = await ApiService.getToken();
    if (token != null) {
      _user = await AuthService.fetchUser();
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> register(
      String name, String email, String password, String passwordConfirmation) async {
    final result =
        await AuthService.register(name, email, password, passwordConfirmation);
    await ApiService.setToken(result.token);
    _user = result.user;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    final result = await AuthService.login(email, password);
    await ApiService.setToken(result.token);
    _user = result.user;
    notifyListeners();
  }

  Future<void> logout() async {
    await AuthService.logout();
    _user = null;
    notifyListeners();
  }
}
