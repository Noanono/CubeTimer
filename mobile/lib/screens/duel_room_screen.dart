import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../providers/auth_provider.dart';
import '../providers/duel_provider.dart';
import '../services/timer_service.dart';

class DuelRoomScreen extends StatefulWidget {
  const DuelRoomScreen({super.key});

  @override
  State<DuelRoomScreen> createState() => _DuelRoomScreenState();
}

class _DuelRoomScreenState extends State<DuelRoomScreen> {
  // Timer state
  bool _holding = false;
  bool _ready = false;
  bool _running = false;
  int _displayMs = 0;
  late final Stopwatch _stopwatch;

  // Scramble
  String _scramble = '';
  String _svgImage = '';
  bool _scrambleLoading = false;

  @override
  void initState() {
    super.initState();
    _stopwatch = Stopwatch();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadScramble();
    });
  }

  Future<void> _loadScramble() async {
    final duel = context.read<DuelProvider>();
    final room = duel.room;
    if (room == null) return;

    setState(() => _scrambleLoading = true);
    try {
      final data = await TimerService.fetchScramble(room.puzzleType,
          seed: room.seed);
      if (mounted) {
        setState(() {
          _scramble = data['scramble'] as String? ?? '';
          _svgImage = data['svgImage'] as String? ?? '';
          _scrambleLoading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _scrambleLoading = false);
    }
  }

  void _onTapDown() {
    final duel = context.read<DuelProvider>();
    if (duel.hasSubmitted || !duel.canStart) return;

    if (_running) {
      _stopwatch.stop();
      final elapsed = _stopwatch.elapsedMilliseconds;
      setState(() {
        _running = false;
        _displayMs = elapsed;
      });
      duel.submitTime(elapsed);
      return;
    }

    setState(() => _holding = true);
    Future.delayed(const Duration(milliseconds: 550), () {
      if (_holding && mounted) {
        setState(() => _ready = true);
      }
    });
  }

  void _onTapUp() {
    final duel = context.read<DuelProvider>();
    if (duel.hasSubmitted || !duel.canStart) return;

    if (_ready && !_running) {
      _stopwatch.reset();
      _stopwatch.start();
      setState(() {
        _running = true;
        _ready = false;
        _holding = false;
      });
      _tick();
      return;
    }
    setState(() {
      _holding = false;
      _ready = false;
    });
  }

  void _tick() {
    if (!_running) return;
    setState(() => _displayMs = _stopwatch.elapsedMilliseconds);
    Future.delayed(const Duration(milliseconds: 16), () {
      if (_running && mounted) _tick();
    });
  }

  Color get _timerColor {
    if (_ready) return Colors.greenAccent;
    if (_holding) return Colors.orangeAccent;
    if (_running) return Colors.white;
    return const Color(0xFF818CF8);
  }

  @override
  Widget build(BuildContext context) {
    final duel = context.watch<DuelProvider>();
    final auth = context.watch<AuthProvider>();
    final room = duel.room;

    if (room == null) {
      return Scaffold(
        backgroundColor: const Color(0xFF111827),
        appBar: AppBar(
          backgroundColor: const Color(0xFF1F2937),
          title: const Text('Duel', style: TextStyle(color: Colors.white)),
          iconTheme: const IconThemeData(color: Colors.white),
        ),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFF111827),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1F2937),
        title: Text('Duel — ${room.code}',
            style: const TextStyle(color: Colors.white, fontFamily: 'monospace')),
        iconTheme: const IconThemeData(color: Colors.white),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () {
            duel.leave();
            Navigator.pop(context);
          },
        ),
      ),
      body: GestureDetector(
        onTapDown: (_) => _onTapDown(),
        onTapUp: (_) => _onTapUp(),
        onTapCancel: () => setState(() {
          _holding = false;
          _ready = false;
        }),
        behavior: HitTestBehavior.opaque,
        child: SafeArea(
          child: Column(
            children: [
              // Waiting for opponent banner
              if (!duel.canStart)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  color: Colors.amber.withAlpha(40),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(
                          height: 14,
                          width: 14,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.amber)),
                      const SizedBox(width: 8),
                      Text(
                        'En attente d\'un adversaire... Code : ${room.code}',
                        style: const TextStyle(
                            color: Colors.amber, fontSize: 13),
                      ),
                    ],
                  ),
                ),

              // Scramble
              Padding(
                padding: const EdgeInsets.all(16),
                child: _scrambleLoading
                    ? const Center(
                        child: SizedBox(
                            height: 20,
                            width: 20,
                            child:
                                CircularProgressIndicator(strokeWidth: 2)))
                    : Text(
                        _scramble,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 14,
                          fontFamily: 'monospace',
                          height: 1.5,
                        ),
                      ),
              ),

              // Timer
              const Spacer(),
              if (duel.hasSubmitted)
                const Text('Temps envoyé !',
                    style: TextStyle(
                        color: Colors.greenAccent,
                        fontSize: 18,
                        fontWeight: FontWeight.w600))
              else if (!duel.canStart)
                const Text('Timer bloqué — attendez un adversaire',
                    style: TextStyle(color: Colors.grey, fontSize: 14)),

              Text(
                _formatDisplay(_displayMs),
                style: TextStyle(
                  fontSize: 64,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'monospace',
                  color: duel.hasSubmitted
                      ? Colors.greenAccent
                      : duel.canStart
                          ? _timerColor
                          : Colors.grey,
                ),
              ),
              const Spacer(),

              // Participants
              Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFF1F2937),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: room.participants.map((p) {
                    final isMe = p.userId == auth.user?.id;
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        children: [
                          Icon(
                            isMe ? Icons.person : Icons.person_outline,
                            color: isMe
                                ? const Color(0xFF818CF8)
                                : Colors.grey,
                            size: 20,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              '${p.userName}${isMe ? ' (vous)' : ''}',
                              style: TextStyle(
                                color: isMe ? Colors.white : Colors.white70,
                                fontWeight:
                                    isMe ? FontWeight.bold : FontWeight.normal,
                              ),
                            ),
                          ),
                          if (p.finished)
                            Text(
                              p.formattedTime ?? 'DNF',
                              style: TextStyle(
                                fontFamily: 'monospace',
                                fontWeight: FontWeight.bold,
                                color: p.dnf
                                    ? Colors.redAccent
                                    : const Color(0xFF818CF8),
                              ),
                            )
                          else
                            const Text('...',
                                style: TextStyle(color: Colors.grey)),
                        ],
                      ),
                    );
                  }).toList(),
                ),
              ),

              // SVG preview
              if (_svgImage.isNotEmpty && !_running)
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  height: 80,
                  child: SvgPicture.string(_svgImage, fit: BoxFit.contain),
                ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatDisplay(int ms) {
    final minutes = ms ~/ 60000;
    final seconds = (ms % 60000) ~/ 1000;
    final hundredths = (ms % 1000) ~/ 10;
    if (minutes > 0) {
      return '$minutes:${seconds.toString().padLeft(2, '0')}.${hundredths.toString().padLeft(2, '0')}';
    }
    return '$seconds.${hundredths.toString().padLeft(2, '0')}';
  }
}
