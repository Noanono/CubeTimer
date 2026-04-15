import 'dart:convert';
import '../models/duel_room.dart';
import 'api_service.dart';

class DuelService {
  static Future<DuelRoom> createRoom(String puzzleType) async {
    final res = await ApiService.post('/duel/create', body: {
      'puzzle_type': puzzleType,
    });
    if (res.statusCode != 201) {
      throw Exception('Erreur lors de la création de la salle');
    }
    return DuelRoom.fromJson(jsonDecode(res.body));
  }

  static Future<DuelRoom> joinRoom(String code) async {
    final res = await ApiService.post('/duel/join', body: {
      'code': code.toUpperCase().trim(),
    });
    if (res.statusCode != 200) {
      final body = jsonDecode(res.body);
      throw Exception(body['message'] ?? 'Impossible de rejoindre la salle');
    }
    return DuelRoom.fromJson(jsonDecode(res.body));
  }

  static Future<DuelRoom> showRoom(String code) async {
    final res = await ApiService.get('/duel/$code');
    if (res.statusCode != 200) {
      throw Exception('Salle introuvable');
    }
    return DuelRoom.fromJson(jsonDecode(res.body));
  }

  static Future<DuelRoom> submitTime(String code,
      {required int timeMs, bool dnf = false}) async {
    final res = await ApiService.post('/duel/$code/submit', body: {
      'time_ms': timeMs,
      'dnf': dnf,
    });
    if (res.statusCode != 200) {
      final body = jsonDecode(res.body);
      throw Exception(body['message'] ?? 'Erreur lors de la soumission');
    }
    return DuelRoom.fromJson(jsonDecode(res.body));
  }
}
