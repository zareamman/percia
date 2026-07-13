/**
 * TMDB Importer Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab navigation
        $('.nav-tab-wrapper a.nav-tab').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var target = $this.attr('href');

            $('.nav-tab-wrapper a.nav-tab').removeClass('nav-tab-active');
            $this.addClass('nav-tab-active');

            $('.tab-content').hide();
            $(target).show();
        });

        // Show/hide TV show specific options
        $('select[name="import_type"], select[name="bulk_type"]').on('change', function() {
            var type = $(this).val();
            $('.tv-show-options, .tv-show-sync-option').toggle(type === 'tv_show');
        });

        // Single sync post type detection
        $('#sync_post_id').on('blur', function() {
            var postId = $(this).val();
            if (!postId) return;

            var $btn = $(this);
            $btn.after('<span class="spinner is-active" style="float: none; margin-left: 10px;"></span>');

            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_get_post_type',
                post_id: postId,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                $btn.next('.spinner').remove();
                if (response.success && response.data.post_type) {
                    var isTV = response.data.post_type === 'tmdb_tv_show';
                    $('.tv-show-sync-option').toggle(isTV);
                    if (isTV) {
                        $('.tv-show-sync-option input').prop('checked', true);
                    }
                }
            });
        });

        // Single sync button
        $(document).on('click', '.sync-single', function() {
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var postType = $btn.data('post-type');

            $btn.prop('disabled', true).text('Syncing...');

            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_sync',
                post_id: postId,
                post_type: postType,
                metadata: true,
                ratings: true,
                taxonomies: true,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                $btn.prop('disabled', false).text('Sync');
                if (response.success) {
                    alert('Synced successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            });
        });

        // Single ratings sync button
        $(document).on('click', '.sync-ratings-single', function() {
            var $btn = $(this);
            var postId = $btn.data('post-id');

            $btn.prop('disabled', true).text('Syncing...');

            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_sync_ratings',
                post_ids: postId,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                $btn.prop('disabled', false).text('Sync Ratings');
                if (response.success) {
                    alert('Ratings synced!');
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            });
        });

        // Job detail modal
        $(document).on('click', '.view-job', function() {
            var jobId = $(this).data('job-id');
            var jobType = $(this).data('job-type');
            openJobModal(jobId, jobType);
        });

        $(document).on('click', '.retry-job', function() {
            var jobId = $(this).data('job-id');
            var jobType = $(this).data('job-type');
            if (confirm('Retry this job?')) {
                retryJob(jobId, jobType);
            }
        });

        $('#job-detail-modal .modal-close').on('click', closeJobModal);
        $('#job-detail-modal').on('click', function(e) {
            if (e.target === this) closeJobModal();
        });

        // Maintenance actions
        $('#tmdb-importer-clear-cache').on('click', function() {
            if (confirm('Clear all cached data?')) {
                runMaintenance('clear_cache', this);
            }
        });

        $('#tmdb-importer-remove-orphaned').on('click', function() {
            if (confirm('Remove orphaned content? This cannot be undone.')) {
                runMaintenance('remove_orphaned', this);
            }
        });

        $('#tmdb-importer-reset-settings').on('click', function() {
            if (confirm('Reset ALL settings to defaults? This cannot be undone.')) {
                if (confirm('Are you absolutely sure?')) {
                    runMaintenance('reset_settings', this);
                }
            }
        });

        $('#tmdb-importer-clear-logs').on('click', function() {
            var days = prompt('Clear logs older than how many days?', '30');
            if (days && !isNaN(days)) {
                if (confirm('Clear logs older than ' + days + ' days?')) {
                    clearLogs(days, this);
                }
            }
        });

        // Use TMDB ID from search results
        $(document).on('click', '.use-tmdb-id', function() {
            var $btn = $(this);
            var id = $btn.data('id');
            var type = $btn.data('type');

            $('#tmdb_id').val(id);
            $('#import_type').val(type).trigger('change');

            $('.nav-tab-wrapper a[href="#single-import"]').trigger('click');

            $('html, body').animate({
                scrollTop: $('#single-import').offset().top - 32
            }, 500);
        });

        function openJobModal(jobId, jobType) {
            var modal = $('#job-detail-modal');
            var content = $('#job-detail-content');
            modal.show();
            content.html('Loading...');

            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_get_job',
                job_id: jobId,
                job_type: jobType,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                if (response.success) {
                    renderJobDetails(content, response.data);
                } else {
                    content.html('<p>Error: ' + response.error + '</p>');
                }
            });
        }

        function closeJobModal() {
            $('#job-detail-modal').hide();
        }

        function retryJob(jobId, jobType) {
            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_retry_job',
                job_id: jobId,
                job_type: jobType,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            });
        }

        function runMaintenance(action, button) {
            var $btn = $(button);
            $btn.prop('disabled', true).text('Working...');

            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_maintenance',
                maintenance_action: action,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                $btn.prop('disabled', false);
                var texts = {
                    clear_cache: 'Clear Cache',
                    remove_orphaned: 'Remove Orphaned Content',
                    reset_settings: 'Reset All Settings'
                };
                $btn.text(texts[action] || action);

                if (response.success) {
                    alert('Success: ' + response.message);
                    if (action === 'reset_settings') location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            });
        }

        function clearLogs(days, button) {
            var $btn = $(button);
            $btn.prop('disabled', true).text('Clearing...');

            $.post(ajaxurl, {
                action: 'tmdb_importer_ajax_clear_logs',
                days: days,
                _wpnonce: tmdbImporterNonce
            }, function(response) {
                $btn.prop('disabled', false).text('Clear Old Logs');
                if (response.success) {
                    alert('Cleared ' + response.count + ' log entries');
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            });
        }

        function renderJobDetails(container, job) {
            var html = '';
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
                job.tmdb_ids.forEach(function(id) {
                    html += '<li>' + id + '</li>';
                });
                html += '</ul></div>';
            }

            if (job.processed_ids && job.processed_ids.length) {
                html += '<div class="job-detail-section"><h4>Processed (' + job.processed_ids.length + ')</h4>';
                html += '<ul class="job-detail-list">';
                job.processed_ids.forEach(function(id) {
                    html += '<li>' + id + '</li>';
                });
                html += '</ul></div>';
            }

            if (job.failed_ids && job.failed_ids.length) {
                html += '<div class="job-detail-section"><h4>Failed (' + job.failed_ids.length + ')</h4>';
                html += '<ul class="job-detail-list error-list">';
                job.failed_ids.forEach(function(id) {
                    html += '<li>' + id + '</li>';
                });
                html += '</ul></div>';
            }

            if (job.errors && job.errors.length) {
                html += '<div class="job-detail-section"><h4>Errors (' + job.errors.length + ')</h4>';
                html += '<ul class="job-detail-list error-list">';
                job.errors.forEach(function(err) {
                    html += '<li>' + err + '</li>';
                });
                html += '</ul></div>';
            }

            container.html(html);
        }
    });
})(jQuery);