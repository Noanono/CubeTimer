class Solve {
  final int id;
  final int timeMs;
  final String formattedTime;
  final String scramble;
  final bool dnf;
  final bool plus2;
  final String source;
  final String createdAt;

  Solve({
    required this.id,
    required this.timeMs,
    required this.formattedTime,
    required this.scramble,
    required this.dnf,
    required this.plus2,
    required this.source,
    required this.createdAt,
  });

  factory Solve.fromJson(Map<String, dynamic> json) {
    return Solve(
      id: json['id'] as int,
      timeMs: json['time_ms'] as int? ?? 0,
      formattedTime: json['formatted_time'] as String? ?? 'DNF',
      scramble: json['scramble'] as String? ?? '',
      dnf: json['dnf'] as bool? ?? false,
      plus2: json['plus2'] as bool? ?? false,
      source: json['source'] as String? ?? 'solo',
      createdAt: json['created_at'] as String? ?? '',
    );
  }
}
