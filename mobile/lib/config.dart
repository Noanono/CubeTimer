class AppConfig {
  // Change this to your server's IP/URL
  // For Android emulator: 10.0.2.2
  // For iOS simulator: 127.0.0.1
  // For real device: your LAN IP (e.g. 192.168.1.x)
  static const String apiBaseUrl = 'http://127.0.0.1:8000/api';

  // Reverb WebSocket config
  static const String wsHost = '127.0.0.1';
  static const int wsPort = 8080;

  static const List<Map<String, String>> puzzleTypes = [
    {'key': '333', 'label': '3x3x3'},
    {'key': '222so', 'label': '2x2x2'},
    {'key': '444wca', 'label': '4x4x4'},
    {'key': '555wca', 'label': '5x5x5'},
    {'key': 'pyrso', 'label': 'Pyraminx'},
    {'key': 'mgmp', 'label': 'Megaminx'},
    {'key': 'skbso', 'label': 'Skewb'},
    {'key': 'sqrs', 'label': 'Square-1'},
  ];
}
