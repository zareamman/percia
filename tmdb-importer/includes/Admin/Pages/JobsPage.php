<?php
/**
 * Jobs Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$importJobRepo = new \TMDBImporter\Repositories\Database\ImportJobRepository();
$syncJobRepo = new \TMDBImporter\Repositories\Database\SyncJobRepository();

$importJobs = $importJobRepo->findByStatus('pending');
$importJobs = array_merge($importJobs, $importJobRepo->findByStatus('running'));
$importJobs = array_merge($importJobs, $importJobRepo->findByStatus('completed'));
$importJobs = array_merge($importJobs, $importJobRepo->findByStatus('failed'));

$syncJobs = $syncJobRepo->findByStatus('pending');
$syncJobs = array_merge($syncJobs, $syncJobRepo->findByStatus('running'));
$syncJobs = array_merge($syncJobs, $syncJobRepo->findByStatus('completed'));
$syncJobs = array_merge($syncJobs, $syncJobRepo->findByStatus('failed'));
?>

<div class="wrap">
    <h1>Import & Sync Jobs</h1>

    <div class="tmdb-importer-jobs-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#import-jobs" class="nav-tab nav-tab-active">Import Jobs</a>
            <a href="#sync-jobs" class="nav-tab">Sync Jobs</a>
        </nav>
    </div>

    <div id="import-jobs" class="tab-content">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Items</th>
                    <th>Created</th>
                    <th>Started</th>
                    <th>Completed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($importJobs as $job): ?>
                    <tr>
                        <td><?php echo $job->getId(); ?></td>
                        <td><?php echo esc_html($job->getType()); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($job->getStatus()); ?>">
                                <?php echo esc_html(ucfirst($job->getStatus())); ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $job->getProgress(); ?>%"></div>
                            </div>
                            <span><?php echo number_format($job->getProgress(), 1); ?>%</span>
                        </td>
                        <td>
                            Processed: <?php echo count($job->getProcessedIds()); ?> /
                            Failed: <?php echo count($job->getFailedIds()); ?> /
                            Total: <?php echo count($job->getTmdbIds()); ?>
                        </td>
                        <td><?php echo esc_html($job->getStartedAt() ? date('Y-m-d H:i:s', strtotime($job->getStartedAt())) : ($job->getScheduledAt() ? date('Y-m-d H:i:s', strtotime($job->getScheduledAt())) : '—')); ?></td>
                        <td><?php echo esc_html($job->getStartedAt() ? date('Y-m-d H:i:s', strtotime($job->getStartedAt())) : '—'); ?></td>
                        <td><?php echo esc_html($job->getCompletedAt() ? date('Y-m-d H:i:s', strtotime($job->getCompletedAt())) : '—'); ?></td>
                        <td>
                            <?php if ($job->canRetry()): ?>
                                <button type="button" class="button button-small retry-job" data-job-id="<?php echo $job->getId(); ?>" data-job-type="import">Retry</button>
                            <?php endif; ?>
                            <button type="button" class="button button-small view-job" data-job-id="<?php echo $job->getId(); ?>" data-job-type="import">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="sync-jobs" class="tab-content" style="display: none;">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Items</th>
                    <th>Created</th>
                    <th>Started</th>
                    <th>Completed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($syncJobs as $job): ?>
                    <tr>
                        <td><?php echo $job->getId(); ?></td>
                        <td><?php echo esc_html($job->getType()); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($job->getStatus()); ?>">
                                <?php echo esc_html(ucfirst($job->getStatus())); ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $job->getProgress(); ?>%"></div>
                            </div>
                            <span><?php echo number_format($job->getProgress(), 1); ?>%</span>
                        </td>
                        <td>
                            Processed: <?php echo count($job->getProcessedIds()); ?> /
                            Failed: <?php echo count($job->getFailedIds()); ?> /
                            Total: <?php echo count($job->getObjectIds()); ?>
                        </td>
                        <td><?php echo esc_html($job->getScheduledAt() ? date('Y-m-d H:i:s', strtotime($job->getScheduledAt())) : '—'); ?></td>
                        <td><?php echo esc_html($job->getStartedAt() ? date('Y-m-d H:i:s', strtotime($job->getStartedAt())) : '—'); ?></td>
                        <td><?php echo esc_html($job->getCompletedAt() ? date('Y-m-d H:i:s', strtotime($job->getCompletedAt())) : '—'); ?></td>
                        <td>
                            <?php if ($job->canRetry()): ?>
                                <button type="button" class="button button-small retry-job" data-job-id="<?php echo $job->getId(); ?>" data-job-type="sync">Retry</button>
                            <?php endif; ?>
                            <button type="button" class="button button-small view-job" data-job-id="<?php echo $job->getId(); ?>" data-job-type="sync">View</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="job-detail-modal" class="tmdb-importer-modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Job Details</h2>
            <div id="job-detail-content">Loading...</div>
        </div>
    </div>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-running { background: #cce5ff; color: #004085; }
.status-completed { background: #d4edda; color: #155724; }
.status-failed { background: #f8d7da; color: #721c24; }
.status-cancelled { background: #e2e3e5; color: #383d41; }

.progress-bar {
    width: 100px;
    height: 10px;
    background: #f1f1f1;
    border-radius: 5px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
}
.progress-fill {
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
}

.tmdb-importer-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100000;
}
.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}
.modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
}
.job-detail-section {
    margin-bottom: 20px;
}
.job-detail-section h4 {
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}
.job-detail-list {
    list-style: none;
    padding: 0;
}
.job-detail-list li {
    padding: 5px 0;
    border-bottom: 1px solid #f5f5f5;
    font-family: monospace;
    font-size: 13px;
}
.error-list li {
    color: #d63638;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.nav-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            this.classList.add('nav-tab-active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.querySelector(this.getAttribute('href')).style.display = 'block';
        });
    });

    document.querySelectorAll('.view-job').forEach(btn => {
        btn.addEventListener('click', function() {
            const jobId = this.dataset.jobId;
            const jobType = this.dataset.jobType;
            openJobModal(jobId, jobType);
        });
    });

    document.querySelectorAll('.retry-job').forEach(btn => {
        btn.addEventListener('click', function() {
            const jobId = this.dataset.jobId;
            const jobType = this.dataset.jobType;
            if (confirm('Retry this job?')) {
                retryJob(jobId, jobType);
            }
        });
    });

    document.querySelector('.modal-close').addEventListener('click', closeModal);
    document.querySelector('.tmdb-importer-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    function openJobModal(jobId, jobType) {
        const modal = document.getElementById('job-detail-modal');
        const content = document.getElementById('job-detail-content');
        modal.style.display = 'flex';
        content.innerHTML = 'Loading...';

        fetch(ajaxurl + '?action=tmdb_importer_ajax_get_job&job_id=' + jobId + '&job_type=' + jobType + '&_wpnonce=' + tmdbImporterNonce)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderJobDetails(content, data.data);
                } else {
                    content.innerHTML = '<p>Error: ' + data.error + '</p>';
                }
            });
    }

    function closeModal() {
        document.getElementById('job-detail-modal').style.display = 'none';
    }

    function retryJob(jobId, jobType) {
        fetch(ajaxurl + '?action=tmdb_importer_ajax_retry_job&job_id=' + jobId + '&job_type=' + jobType + '&_wpnonce=' + tmdbImporterNonce)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }

    function renderJobDetails(container, job) {
        let html = '';
        html += '<div class="job-detail-section"><h4>Basic Info</h4>';
        html += '<ul class="job-detail-list">';
        html += '<li>ID: ' + job.id + '</li>';
        html += '<li>Type: ' + job.type + '</li>';
        html += '<li>Status: ' + job.status + '</li>';
        html += '<li>Progress: ' + job.progress + '%</li>';
        html += '</ul></div>';

        if (job.tmdb_ids && job.tmdb_ids.length) {
            html += '<div class="job-detail-section"><h4>TMDB IDs (' + job.tmdb_ids.length + ')</h4>';
            html += '<ul class="job-detail-list">';
            job.tmdb_ids.forEach(id => html += '<li>' + id + '</li>');
            html += '</ul></div>';
        }

        if (job.processed_ids && job.processed_ids.length) {
            html += '<div class="job-detail-section"><h4>Processed (' + job.processed_ids.length + ')</h4>';
            html += '<ul class="job-detail-list">';
            job.processed_ids.forEach(id => html += '<li>' + id + '</li>');
            html += '</ul></div>';
        }

        if (job.failed_ids && job.failed_ids.length) {
            html += '<div class="job-detail-section"><h4>Failed (' + job.failed_ids.length + ')</h4>';
            html += '<ul class="job-detail-list error-list">';
            job.failed_ids.forEach(id => html += '<li>' + id + '</li>');
            html += '</ul></div>';
        }

        if (job.errors && job.errors.length) {
            html += '<div class="job-detail-section"><h4>Errors (' + job.errors.length + ')</h4>';
            html += '<ul class="job-detail-list error-list">';
            job.errors.forEach(err => html += '<li>' + err + '</li>');
            html += '</ul></div>';
        }

        container.innerHTML = html;
    }
});
</script>