class DuelParticipant {
  final int userId;
  final String userName;
  final int? timeMs;
  final bool dnf;
  final String? formattedTime;
  final bool finished;

  DuelParticipant({
    required this.userId,
    required this.userName,
    this.timeMs,
    required this.dnf,
    this.formattedTime,
    required this.finished,
  });

  factory DuelParticipant.fromJson(Map<String, dynamic> json) {
    return DuelParticipant(
      userId: json['user_id'] as int,
      userName: json['user_name'] as String,
      timeMs: json['time_ms'] as int?,
      dnf: json['dnf'] as bool? ?? false,
      formattedTime: json['formatted_time'] as String?,
      finished: json['finished'] as bool? ?? false,
    );
  }
}

class DuelRoom {
  final String code;
  final String puzzleType;
  final String seed;
  final String scrambleText;
  final String status;
  final List<DuelParticipant> participants;

  DuelRoom({
    required this.code,
    required this.puzzleType,
    required this.seed,
    required this.scrambleText,
    required this.status,
    required this.participants,
  });

  factory DuelRoom.fromJson(Map<String, dynamic> json) {
    return DuelRoom(
      code: json['code'] as String,
      puzzleType: json['puzzle_type'] as String,
      seed: json['seed'] as String? ?? '',
      scrambleText: json['scramble_text'] as String? ?? '',
      status: json['status'] as String,
      participants: (json['participants'] as List? ?? [])
          .map((p) => DuelParticipant.fromJson(p as Map<String, dynamic>))
          .toList(),
    );
  }
}
