import 'dart:convert';
import 'api_service.dart';

class StatsService {
  static Future<Map<String, dynamic>> fetchStats(String puzzle) async {
    final res = await ApiService.get('/statistics', queryParams: {'puzzle': puzzle});
    if (res.statusCode != 200) {
      throw Exception('Erreur lors de la récupération des statistiques');
    }
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  static Future<void> deleteSolve(int id) async {
    final res = await ApiService.delete('/solves/$id');
    if (res.statusCode != 200) {
      throw Exception('Erreur lors de la suppression');
    }
  }

  static Future<Map<String, dynamic>> toggleDnf(int id) async {
    final res = await ApiService.patch('/solves/$id/dnf');
    if (res.statusCode != 200) {
      throw Exception('Erreur lors du toggle DNF');
    }
    return jsonDecode(res.body) as Map<String, dynamic>;
  }
}
