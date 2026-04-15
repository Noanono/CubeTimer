import 'package:flutter/material.dart';
import '../services/timer_service.dart';

class TimerProvider extends ChangeNotifier {
  String _puzzleType = '333';
  String _scramble = '';
  String _svgImage = '';
  bool _loading = false;
  int? _lastTimeMs;

  String get puzzleType => _puzzleType;
  String get scramble => _scramble;
  String get svgImage => _svgImage;
  bool get loading => _loading;
  int? get lastTimeMs => _lastTimeMs;

  void setPuzzleType(String type) {
    _puzzleType = type;
    _scramble = '';
    _svgImage = '';
    _lastTimeMs = null;
    notifyListeners();
    fetchScramble();
  }

  Future<void> fetchScramble({String? seed}) async {
    _loading = true;
    notifyListeners();

    try {
      final data = await TimerService.fetchScramble(_puzzleType, seed: seed);
      _scramble = data['scramble'] as String? ?? '';
      _svgImage = data['svgImage'] as String? ?? '';
    } catch (_) {
      _scramble = 'Erreur de génération';
      _svgImage = '';
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> saveSolve(int timeMs, {bool dnf = false, bool plus2 = false}) async {
    if (_scramble.isEmpty) return;

    try {
      await TimerService.saveSolve(
        puzzleType: _puzzleType,
        scramble: _scramble,
        timeMs: timeMs,
        dnf: dnf,
        plus2: plus2,
      );
      _lastTimeMs = timeMs;
    } catch (_) {}

    notifyListeners();
    fetchScramble();
  }

  String formatTime(int ms) {
    final minutes = ms ~/ 60000;
    final seconds = (ms % 60000) ~/ 1000;
    final hundredths = (ms % 1000) ~/ 10;
    if (minutes > 0) {
      return '$minutes:${seconds.toString().padLeft(2, '0')}.${hundredths.toString().padLeft(2, '0')}';
    }
    return '$seconds.${hundredths.toString().padLeft(2, '0')}';
  }
}
