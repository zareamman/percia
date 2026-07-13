<?php
/**
 * Dashboard Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$services = $this->getServices();
$logger = $services['logger'] ?? null;
$importJobRepo = new \TMDBImporter\Repositories\Database\ImportJobRepository();
$syncJobRepo = new \TMDBImporter\Repositories\Database\SyncJobRepository();

$recentImports = $importJobRepo->findByStatus(\TMDBImporter\Domain\ImportJob::STATUS_COMPLETED);
$recentSyncs = $syncJobRepo->findByStatus(\TMDBImporter\Domain\SyncJob::STATUS_COMPLETED);

$stats = [
    'movies' => wp_count_posts('tmdb_movie')->publish ?? 0,
    'tv_shows' => wp_count_posts('tmdb_tv_show')->publish ?? 0,
    'import_jobs' => count($importJobRepo->findByStatus(\TMDBImporter\Domain\ImportJob::STATUS_PENDING)),
    'sync_jobs' => count($syncJobRepo->findByStatus(\TMDBImporter\Domain\SyncJob::STATUS_PENDING)),
];
?>

<div class="wrap">
    <h1>TMDB Importer Dashboard</h1>

    <div class="tmdb-importer-stats">
        <div class="stat-box">
            <h3><?php echo $stats['movies']; ?></h3>
            <p>Movies Imported</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['tv_shows']; ?></h3>
            <p>TV Shows Imported</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['import_jobs']; ?></h3>
            <p>Pending Imports</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['sync_jobs']; ?></h3>
            <p>Pending Syncs</p>
        </div>
    </div>

    <h2>Quick Actions</h2>
    <div class="tmdb-importer-actions">
        <a href="<?php echo admin_url('admin.php?page=tmdb-importer-import'); ?>" class="button button-primary">Import Movie</a>
        <a href="<?php echo admin_url('admin.php?page=tmdb-importer-import'); ?>" class="button button-primary">Import TV Show</a>
        <a href="<?php echo admin_url('admin.php?page=tmdb-importer-sync'); ?>" class="button">Sync Content</a>
        <a href="<?php echo admin_url('admin.php?page=tmdb-importer-jobs'); ?>" class="button">View Jobs</a>
    </div>

    <div class="tmdb-importer-recent">
        <div class="recent-section">
            <h2>Recent Imports</h2>
            <?php if (empty($recentImports)): ?>
                <p>No recent imports.</p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>TMDB IDs</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentImports, 0, 10) as $job): ?>
                            <tr>
                                <td><?php echo esc_html($job->getType()); ?></td>
                                <td><?php echo esc_html(implode(', ', array_slice($job->getTmdbIds(), 0, 5))); ?><?php echo count($job->getTmdbIds()) > 5 ? '...' : ''; ?></td>
                                <td><span class="status-<?php echo esc_attr($job->getStatus()); ?>"><?php echo esc_html(ucfirst($job->getStatus())); ?></span></td>
                                <td><?php echo esc_html($job->getProgress()); ?>%</td>
                                <td><?php echo esc_html($job->getCompletedAt() ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="recent-section">
            <h2>Recent Syncs</h2>
            <?php if (empty($recentSyncs)): ?>
                <p>No recent syncs.</p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Object IDs</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentSyncs, 0, 10) as $job): ?>
                            <tr>
                                <td><?php echo esc_html($job->getType()); ?></td>
                                <td><?php echo esc_html(implode(', ', array_slice($job->getObjectIds(), 0, 5))); ?><?php echo count($job->getObjectIds()) > 5 ? '...' : ''; ?></td>
                                <td><span class="status-<?php echo esc_attr($job->getStatus()); ?>"><?php echo esc_html(ucfirst($job->getStatus())); ?></span></td>
                                <td><?php echo esc_html($job->getProgress()); ?>%</td>
                                <td><?php echo esc_html($job->getCompletedAt() ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.tmdb-importer-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}
.stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    flex: 1;
    text-align: center;
}
.stat-box h3 {
    margin: 0;
    font-size: 32px;
    color: #0073aa;
}
.stat-box p {
    margin: 5px 0 0;
    color: #646970;
}
.tmdb-importer-actions {
    margin: 20px 0;
}
.tmdb-importer-actions .button {
    margin-right: 10px;
}
.tmdb-importer-recent {
    display: flex;
    gap: 20px;
}
.tmdb-importer-recent .recent-section {
    flex: 1;
}
.status-pending { color: #d63638; }
.status-running { color: #0073aa; }
.status-completed { color: #00a32a; }
.status-failed { color: #d63638; }
.status-cancelled { color: #646970; }
</style>