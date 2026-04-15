import 'dart:async';
import 'package:flutter/material.dart';
import '../models/duel_room.dart';
import '../services/duel_service.dart';
import '../services/websocket_service.dart';

class DuelProvider extends ChangeNotifier {
  DuelRoom? _room;
  bool _loading = false;
  String? _error;
  bool _hasSubmitted = false;
  WebSocketService? _ws;
  Timer? _pollTimer;

  DuelRoom? get room => _room;
  bool get loading => _loading;
  String? get error => _error;
  bool get hasSubmitted => _hasSubmitted;
  bool get canStart => (_room?.participants.length ?? 0) >= 2;

  Future<void> createRoom(String puzzleType) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      _room = await DuelService.createRoom(puzzleType);
      _hasSubmitted = false;
      _startListening();
    } catch (e) {
      _error = e.toString();
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> joinRoom(String code) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      _room = await DuelService.joinRoom(code);
      _hasSubmitted = false;
      _startListening();
    } catch (e) {
      _error = e.toString();
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> loadRoom(String code) async {
    _loading = true;
    notifyListeners();

    try {
      _room = await DuelService.showRoom(code);
      _startListening();
    } catch (e) {
      _error = e.toString();
    }

    _loading = false;
    notifyListeners();
  }

  Future<void> submitTime(int timeMs, {bool dnf = false}) async {
    if (_room == null || _hasSubmitted) return;

    try {
      _room = await DuelService.submitTime(_room!.code,
          timeMs: timeMs, dnf: dnf);
      _hasSubmitted = true;
    } catch (e) {
      _error = e.toString();
    }

    notifyListeners();
  }

  void _startListening() {
    if (_room == null) return;

    // WebSocket for real-time duel events
    _ws?.disconnect();
    _ws = WebSocketService(
      channelName: 'duel.${_room!.code}',
      eventName: 'time.submitted',
      onEvent: (data) {
        // Refresh room data when opponent submits
        _refreshRoom();
      },
    );
    _ws!.connect();

    // Poll to check if opponent joined (every 3s)
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) {
      if (!canStart) _refreshRoom();
    });
  }

  Future<void> _refreshRoom() async {
    if (_room == null) return;
    try {
      _room = await DuelService.showRoom(_room!.code);
      notifyListeners();
    } catch (_) {}
  }

  void leave() {
    _ws?.disconnect();
    _ws = null;
    _pollTimer?.cancel();
    _pollTimer = null;
    _room = null;
    _hasSubmitted = false;
    _error = null;
    notifyListeners();
  }

  @override
  void dispose() {
    _ws?.disconnect();
    _pollTimer?.cancel();
    super.dispose();
  }
}
