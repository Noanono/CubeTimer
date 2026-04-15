import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:fl_chart/fl_chart.dart';
import '../config.dart';
import '../providers/stats_provider.dart';

class StatisticsScreen extends StatefulWidget {
  const StatisticsScreen({super.key});

  @override
  State<StatisticsScreen> createState() => _StatisticsScreenState();
}

class _StatisticsScreenState extends State<StatisticsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<StatsProvider>().fetchStats();
    });
  }

  @override
  Widget build(BuildContext context) {
    final stats = context.watch<StatsProvider>();

    return Container(
      color: const Color(0xFF111827),
      child: stats.loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () => stats.fetchStats(),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Puzzle selector
                  Row(
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
                          value: stats.puzzleType,
                          dropdownColor: const Color(0xFF1F2937),
                          style:
                              const TextStyle(color: Colors.white, fontSize: 14),
                          underline: const SizedBox(),
                          items: AppConfig.puzzleTypes
                              .map((p) => DropdownMenuItem(
                                    value: p['key'],
                                    child: Text(p['label']!),
                                  ))
                              .toList(),
                          onChanged: (v) {
                            if (v != null) stats.setPuzzleType(v);
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Stats grid
                  _buildStatsGrid(stats.stats),
                  const SizedBox(height: 16),

                  // Chart
                  if (stats.chartData.isNotEmpty) ...[
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1F2937),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      height: 200,
                      child: LineChart(
                        LineChartData(
                          gridData: const FlGridData(show: false),
                          titlesData: const FlTitlesData(show: false),
                          borderData: FlBorderData(show: false),
                          lineBarsData: [
                            LineChartBarData(
                              spots: stats.chartData
                                  .map((d) => FlSpot(
                                        (d['x'] as num).toDouble(),
                                        (d['y'] as num).toDouble(),
                                      ))
                                  .toList(),
                              isCurved: true,
                              color: const Color(0xFF818CF8),
                              barWidth: 2,
                              dotData: const FlDotData(show: false),
                              belowBarData: BarAreaData(
                                show: true,
                                color: const Color(0xFF818CF8).withAlpha(25),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Solve history
                  const Text('Historique',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),

                  if (stats.recentSolves.isEmpty)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.all(32),
                        child: Text('Aucun résultat pour ce puzzle.',
                            style: TextStyle(color: Colors.grey)),
                      ),
                    )
                  else
                    ...stats.recentSolves.map((solve) => Dismissible(
                          key: ValueKey(solve.id),
                          direction: DismissDirection.endToStart,
                          background: Container(
                            alignment: Alignment.centerRight,
                            padding: const EdgeInsets.only(right: 20),
                            color: Colors.redAccent,
                            child: const Icon(Icons.delete, color: Colors.white),
                          ),
                          onDismissed: (_) => stats.deleteSolve(solve.id),
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 4),
                            padding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 10),
                            decoration: BoxDecoration(
                              color: const Color(0xFF1F2937),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Row(
                                    children: [
                                      Text(
                                        solve.formattedTime,
                                        style: TextStyle(
                                          fontFamily: 'monospace',
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16,
                                          color: solve.dnf
                                              ? Colors.redAccent
                                              : const Color(0xFF818CF8),
                                          decoration: solve.dnf
                                              ? TextDecoration.lineThrough
                                              : null,
                                        ),
                                      ),
                                      if (solve.source == 'duel')
                                        Container(
                                          margin: const EdgeInsets.only(left: 6),
                                          padding: const EdgeInsets.symmetric(
                                              horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: Colors.purple.withAlpha(40),
                                            borderRadius:
                                                BorderRadius.circular(4),
                                          ),
                                          child: const Text('⚔️',
                                              style: TextStyle(fontSize: 10)),
                                        ),
                                    ],
                                  ),
                                ),
                                // DNF toggle
                                GestureDetector(
                                  onTap: () => stats.toggleDnf(solve.id),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      border: Border.all(
                                          color: solve.dnf
                                              ? Colors.greenAccent
                                              : Colors.orangeAccent),
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Text(
                                      solve.dnf ? '↩' : 'DNF',
                                      style: TextStyle(
                                        color: solve.dnf
                                            ? Colors.greenAccent
                                            : Colors.orangeAccent,
                                        fontSize: 11,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        )),
                ],
              ),
            ),
    );
  }

  Widget _buildStatsGrid(Map<String, dynamic> s) {
    final items = [
      ('Solves', '${s['count'] ?? '-'}'),
      ('Best', '${s['best'] ?? '-'}'),
      ('Pire', '${s['worst'] ?? '-'}'),
      ('Moy.', '${s['avg'] ?? '-'}'),
      ('Ao5', '${s['ao5'] ?? '-'}'),
      ('Ao12', '${s['ao12'] ?? '-'}'),
      ('Ao100', '${s['ao100'] ?? '-'}'),
    ];

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: items
          .map((item) => Container(
                width: (MediaQuery.of(context).size.width - 48) / 4,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFF1F2937),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Text(item.$1,
                        style: const TextStyle(
                            color: Colors.grey, fontSize: 10)),
                    const SizedBox(height: 4),
                    Text(item.$2,
                        style: const TextStyle(
                            color: Colors.white,
                            fontFamily: 'monospace',
                            fontWeight: FontWeight.bold,
                            fontSize: 16)),
                  ],
                ),
              ))
          .toList(),
    );
  }
}
