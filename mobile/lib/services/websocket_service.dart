import 'dart:async';
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';
import '../config.dart';

class WebSocketService {
  WebSocketChannel? _channel;
  StreamSubscription? _subscription;
  final void Function(Map<String, dynamic> data) onEvent;
  final String channelName;
  final String eventName;

  WebSocketService({
    required this.channelName,
    required this.eventName,
    required this.onEvent,
  });

  void connect() {
    // Reverb uses Pusher protocol over raw WebSocket
    final uri = Uri.parse(
        'ws://${AppConfig.wsHost}:${AppConfig.wsPort}/app/cubetimer?protocol=7&client=dart&version=1.0');
    _channel = WebSocketChannel.connect(uri);

    _subscription = _channel!.stream.listen((message) {
      final data = jsonDecode(message as String) as Map<String, dynamic>;
      final event = data['event'] as String?;

      if (event == 'pusher:connection_established') {
        // Subscribe to channel
        _channel!.sink.add(jsonEncode({
          'event': 'pusher:subscribe',
          'data': {'channel': channelName},
        }));
      } else if (event == eventName) {
        final payload =
            jsonDecode(data['data'] as String) as Map<String, dynamic>;
        onEvent(payload);
      }
    }, onError: (_) {
      // Reconnect after 3s on error
      Future.delayed(const Duration(seconds: 3), connect);
    });
  }

  void disconnect() {
    _subscription?.cancel();
    _channel?.sink.close();
    _channel = null;
  }
}
