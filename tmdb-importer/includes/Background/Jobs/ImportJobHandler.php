<?php
namespace TMDBImporter\Background\Jobs;

use TMDBImporter\Domain\ImportJob;
use TMDBImporter\Services\ImportService;
use TMDBImporter\Infrastructure\Logging\Logger;

class ImportJobHandler extends BackgroundJob
{
    public function run(): void
    {
        $this->job->setStatus(ImportJob::STATUS_RUNNING);
        $this->job->setStartedAt(current_time('mysql', true));

        $tmdbIds = $this->job->getTmdbIds();
        $options = $this->job->getOptions();
        $type = $this->job->getType();

        try {
            if ($type === ImportJob::TYPE_MOVIE) {
                $this->importMovies($tmdbIds, $options);
            } elseif ($type === ImportJob::TYPE_TV_SHOW) {
                $this->importTVShows($tmdbIds, $options);
            } elseif ($type === ImportJob::TYPE_SEASON) {
                $this->importSeasons($tmdbIds, $options);
            }

            $this->job->setStatus(ImportJob::STATUS_COMPLETED);
        } catch (\Throwable $e) {
            $this->job->setStatus(ImportJob::STATUS_FAILED);
            $this->job->addError($e->getMessage());
            $this->error('Import job failed', $e);
        } finally {
            $this->job->setCompletedAt(current_time('mysql', true));
        }
    }

    private function importMovies(array $tmdbIds, array $options): void
    {
        $total = count($tmdbIds);
        foreach ($tmdbIds as $index => $tmdbId) {
            try {
                $this->importService->importMovie($tmdbId, $options);
                $this->job->addProcessedId($tmdbId);
                $this->updateProgress(($index + 1) / $total * 100);
            } catch (\Throwable $e) {
                $this->job->addFailedId($tmdbId);
                $this->job->addError("Movie {$tmdbId}: " . $e->getMessage());
                $this->error("Failed to import movie {$tmdbId}", $e);
            }
        }
    }

    private function importTVShows(array $tmdbIds, array $options): void
    {
        $total = count($tmdbIds);
        foreach ($tmdbIds as $index => $tmdbId) {
            try {
                $this->importService->importTVShow($tmdbId, $options);
                $this->job->addProcessedId($tmdbId);
                $this->updateProgress(($index + 1) / $total * 100);
            } catch (\Throwable $e) {
                $this->job->addFailedId($tmdbId);
                $this->job->addError("TV Show {$tmdbId}: " . $e->getMessage());
                $this->error("Failed to import TV show {$tmdbId}", $e);
            }
        }
    }

    private function importSeasons(array $seasonIds, array $options): void
    {
        $total = count($seasonIds);
        foreach ($seasonIds as $index => $seasonData) {
            try {
                $this->importService->importSeason(
                    $seasonData['show_id'],
                    $seasonData['season_number'],
                    $options
                );
                $this->job->addProcessedId($seasonData['id']);
                $this->updateProgress(($index + 1) / $total * 100);
            } catch (\Throwable $e) {
                $this->job->addFailedId($seasonData['id']);
                $this->job->addError("Season {$seasonData['id']}: " . $e->getMessage());
                $this->error("Failed to import season", $e);
            }
        }
    }
}