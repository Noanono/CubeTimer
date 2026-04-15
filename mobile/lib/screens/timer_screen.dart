import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../config.dart';
import '../providers/timer_provider.dart';

class TimerScreen extends StatefulWidget {
  const TimerScreen({super.key});

  @override
  State<TimerScreen> createState() => _TimerScreenState();
}

class _TimerScreenState extends State<TimerScreen> {
  // Timer state
  bool _holding = false;
  bool _ready = false;
  bool _running = false;
  int _displayMs = 0;
  late final Stopwatch _stopwatch;

  @override
  void initState() {
    super.initState();
    _stopwatch = Stopwatch();
    // Fetch first scramble
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<TimerProvider>().fetchScramble();
    });
  }

  void _onTapDown() {
    if (_running) {
      // Stop timer
      _stopwatch.stop();
      final elapsed = _stopwatch.elapsedMilliseconds;
      setState(() {
        _running = false;
        _displayMs = elapsed;
      });
      context.read<TimerProvider>().saveSolve(elapsed);
      return;
    }

    // Start hold
    setState(() => _holding = true);
    Future.delayed(const Duration(milliseconds: 550), () {
      if (_holding && mounted) {
        setState(() => _ready = true);
      }
    });
  }

  void _onTapUp() {
    if (_ready && !_running) {
      // Start timer
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
    final timer = context.watch<TimerProvider>();

    return GestureDetector(
      onTapDown: (_) => _onTapDown(),
      onTapUp: (_) => _onTapUp(),
      onTapCancel: () => setState(() {
        _holding = false;
        _ready = false;
      }),
      behavior: HitTestBehavior.opaque,
      child: Container(
        color: const Color(0xFF111827),
        child: SafeArea(
          child: Column(
            children: [
              // Puzzle selector
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: Row(
                  children: [
                    const Text('Puzzle : ',
                        style: TextStyle(color: Colors.grey, fontSize: 14)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1F2937),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: DropdownButton<String>(
                        value: timer.puzzleType,
                        dropdownColor: const Color(0xFF1F2937),
                        style: const TextStyle(color: Colors.white, fontSize: 14),
                        underline: const SizedBox(),
                        items: AppConfig.puzzleTypes
                            .map((p) => DropdownMenuItem(
                                  value: p['key'],
                                  child: Text(p['label']!),
                                ))
                            .toList(),
                        onChanged: (v) {
                          if (v != null && !_running) timer.setPuzzleType(v);
                        },
                      ),
                    ),
                  ],
                ),
              ),

              // Scramble text
              Padding(
                padding: const EdgeInsets.all(16),
                child: timer.loading
                    ? const Center(
                        child: SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2)))
                    : Text(
                        timer.scramble,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 16,
                          fontFamily: 'monospace',
                          height: 1.5,
                        ),
                      ),
              ),

              // Timer display
              const Spacer(),
              Text(
                _formatDisplay(_displayMs),
                style: TextStyle(
                  fontSize: 72,
                  fontWeight: FontWeight.bold,
                  fontFamily: 'monospace',
                  color: _timerColor,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _running
                    ? 'Tapez pour arrêter'
                    : _holding
                        ? (_ready ? 'Relâchez pour démarrer' : 'Maintenez...')
                        : 'Maintenez pour préparer',
                style: const TextStyle(color: Colors.grey, fontSize: 13),
              ),
              const Spacer(),

              // SVG cube preview
              if (timer.svgImage.isNotEmpty && !_running)
                Container(
                  margin: const EdgeInsets.only(bottom: 16),
                  height: 100,
                  child: SvgPicture.string(
                    timer.svgImage,
                    fit: BoxFit.contain,
                  ),
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
