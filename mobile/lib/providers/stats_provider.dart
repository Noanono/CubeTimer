import 'package:flutter/material.dart';
import '../models/solve.dart';
import '../services/stats_service.dart';

class StatsProvider extends ChangeNotifier {
  String _puzzleType = '333';
  Map<String, dynamic> _stats = {};
  List<Solve> _recentSolves = [];
  List<Map<String, dynamic>> _chartData = [];
  bool _loading = false;

  String get puzzleType => _puzzleType;
  Map<String, dynamic> get stats => _stats;
  List<Solve> get recentSolves => _recentSolves;
  List<Map<String, dynamic>> get chartData => _chartData;
  bool get loading => _loading;

  void setPuzzleType(String type) {
    _puzzleType = type;
    notifyListeners();
    fetchStats();
  }

  Future<void> fetchStats() async {
    _loading = true;
    notifyListeners();

    try {
      final data = await StatsService.fetchStats(_puzzleType);
      _stats = data['stats'] as Map<String, dynamic>? ?? {};
      _recentSolves = (data['recentSolves'] as List? ?? [])
          .map((s) => Solve.fromJson(s as Map<String, dynamic>))
          .toList();
      _chartData = (data['chartData'] as List? ?? [])
          .map((d) => d as Map<String, dynamic>)
          .toList();
    } catch (_) {
      _stats = {};
      _recentSolves = [];
      _chartData = [];
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> deleteSolve(int id) async {
    await StatsService.deleteSolve(id);
    _recentSolves.removeWhere((s) => s.id == id);
    notifyListeners();
    fetchStats(); // refresh stats
  }

  Future<void> toggleDnf(int id) async {
    await StatsService.toggleDnf(id);
    fetchStats(); // refresh everything
  }
}
