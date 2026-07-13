<?php
/**
 * Logs Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$logger = new \TMDBImporter\Infrastructure\Logging\Logger();
$logs = $logger->getRecentLogs(200);
$levels = ['error', 'warning', 'info', 'debug'];
?>

<div class="wrap">
    <h1>TMDB Importer Logs</h1>

    <div class="tmdb-importer-logs-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="tmdb-importer-logs" />
            <select name="log_level">
                <option value="">All Levels</option>
                <?php foreach ($levels as $level): ?>
                    <option value="<?php echo $level; ?>" <?php selected(($_GET['log_level'] ?? ''), $level); ?>><?php echo ucfirst($level); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="limit" placeholder="Limit" value="<?php echo esc_attr($_GET['limit'] ?? 200); ?>" min="10" max="1000" style="width: 80px;" />
            <?php submit_button('Filter', 'secondary', 'filter_logs', false); ?>
            <button type="button" class="button button-secondary" id="tmdb-importer-clear-logs">Clear Old Logs</button>
        </form>
    </div>

    <?php if (!empty($logs)): ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Level</th>
                    <th>Message</th>
                    <th>Context</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr class="log-level-<?php echo esc_attr($log['level']); ?>">
                        <td><?php echo esc_html($log['created_at']); ?></td>
                        <td><span class="log-badge log-<?php echo esc_attr($log['level']); ?>"><?php echo esc_html(strtoupper($log['level'])); ?></span></td>
                        <td><?php echo esc_html($log['message']); ?></td>
                        <td>
                            <?php
                            $context = json_decode($log['context'], true);
                            if ($context) {
                                echo '<pre style="margin: 0; font-size: 11px; max-height: 150px; overflow: auto;">';
                                echo esc_html(json_encode($context, JSON_PRETTY_PRINT));
                                echo '</pre>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No logs found.</p>
    <?php endif; ?>
</div>

<style>
.tmdb-importer-logs-filters {
    margin: 20px 0;
}
.tmdb-importer-logs-filters select,
.tmdb-importer-logs-filters input {
    margin-right: 10px;
}
.log-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.log-emergency, .log-alert, .log-critical, .log-error { background: #f8d7da; color: #721c24; }
.log-warning { background: #fff3cd; color: #856404; }
.log-notice, .log-info { background: #cce5ff; color: #004085; }
.log-debug { background: #e2e3e5; color: #383d41; }
.log-level-error { background-color: #fff0f0; }
.log-level-warning { background-color: #fffbf0; }
.log-level-debug { background-color: #f5f5f5; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('tmdb-importer-clear-logs').addEventListener('click', function() {
        const days = prompt('Clear logs older than how many days?', '30');
        if (days && !isNaN(days)) {
            if (confirm('Clear logs older than ' + days + ' days?')) {
                const btn = this;
                btn.disabled = true;
                btn.textContent = 'Clearing...';

                fetch(ajaxurl + '?action=tmdb_importer_ajax_clear_logs&days=' + days + '&_wpnonce=' + tmdbImporterNonce)
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.textContent = 'Clear Old Logs';
                        if (data.success) {
                            alert('Cleared ' + data.count + ' log entries');
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    });
            }
        }
    });
});
</script>