import 'dart:convert';
import 'api_service.dart';

class TimerService {
  static Future<Map<String, dynamic>> fetchScramble(String puzzle, {String? seed}) async {
    final params = {'puzzle': puzzle};
    if (seed != null && seed.isNotEmpty) params['seed'] = seed;

    final res = await ApiService.get('/scramble', queryParams: params);
    if (res.statusCode != 200) {
      throw Exception('Erreur lors de la récupération du mélange');
    }
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  static Future<Map<String, dynamic>> saveSolve({
    required String puzzleType,
    required String scramble,
    required int timeMs,
    bool dnf = false,
    bool plus2 = false,
  }) async {
    final res = await ApiService.post('/solves', body: {
      'puzzle_type': puzzleType,
      'scramble': scramble,
      'time_ms': timeMs,
      'dnf': dnf,
      'plus2': plus2,
    });
    if (res.statusCode != 201) {
      throw Exception('Erreur lors de la sauvegarde');
    }
    return jsonDecode(res.body) as Map<String, dynamic>;
  }
}
