# ARCHITECTURE.md

## Purpose
Defines how TMDB Importer is built.

## Core Principles

### WordPress First
Prefer:
- HTTP API
- Media API
- Metadata API
- Cache API
- Cron API
- REST API
- Settings API
- Hooks

### Minimal Dependencies
- No Composer
- Native PHP autoloader using spl_autoload_register()
- Self-contained project

### Separation of Concerns
Business logic belongs in Services.

## High-Level Layers

Bootstrap
↓
Autoloader
↓
Application
↓
Services
↓
Repositories
↓
Persistence

## WordPress Objects
- Movie
- TV Show
- Taxonomies
- Media
- Options

## Plugin Objects
- Season
- Episode
- Import Job
- Sync Job
- Links

## Repository Layer
Repositories perform persistence only.

## Service Layer
Business workflows:
- Import
- Sync
- Images
- Videos
- Metadata
- Taxonomies

## Infrastructure
- TMDB Client
- Scheduler
- Cache
- Logging

## Admin
UI only.
Calls Services.

## REST
Validate → Authorize → Service → Response.

## Background Jobs
- Imports
- Sync
- Downloads
- Cleanup

## Caching
Use WordPress Object Cache.

## Security
- Nonces
- Capability checks
- Sanitization
- Escaping
- Prepared SQL

## Coding Standards
- WordPress Coding Standards
- Native PHP autoloading
- Namespaces
- Small focused classes

## Bootstrap
Plugin entry file:
1. Register autoloader
2. Boot application
3. Register hooks

## Extension Points
Expose:
- Actions
- Filters
- Stable public APIs
