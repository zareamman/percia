# ENGINEERING.md

## Purpose

This document defines the engineering standards and coding conventions for TMDB Importer.

All code generated for this project must follow these rules.

When there is a conflict between convenience and architecture, architecture always wins.

---

# General Principles

Always prefer:

- Readability
- Simplicity
- Maintainability
- Performance
- Explicit code over clever code

Code is written for humans first.

---

# WordPress First

Whenever WordPress provides a stable API, use it instead of raw PHP.

Examples include:

- HTTP API
- Media API
- Metadata API
- Object Cache
- REST API
- Settings API
- Cron API
- Roles & Capabilities
- Hooks

Avoid reimplementing WordPress functionality.

---

# Native PHP

The project intentionally avoids Composer.

Use:

- Native PHP
- Namespaces
- spl_autoload_register()

Avoid unnecessary dependencies.

---

# File Organization

One class per file.

File names should match class names.

Namespaces should match the directory structure.

Avoid deeply nested directories.

---

# Class Design

Every class must have a single responsibility.

Classes should remain focused.

Large classes should be split into smaller services.

Avoid "God Classes."

---

# Method Design

Methods should:

- Do one thing
- Be easy to read
- Have descriptive names

Avoid long methods.

Prefer early returns over nested conditions.

---

# Business Logic

Business rules belong only inside Services.

Never place business logic inside:

- Controllers
- Admin pages
- AJAX callbacks
- REST endpoints
- Template files

---

# Repositories

Repositories are responsible only for persistence.

Repositories may:

- Find
- Save
- Delete
- Query

Repositories must never contain business rules.

---

# Domain Objects

Domain objects represent business entities.

They should not know how data is stored.

Avoid direct database access inside domain objects.

---

# Dependency Direction

Dependencies should always point inward.

Infrastructure depends on Services.

Services depend on Repositories.

Repositories depend on persistence.

Never reverse these relationships.

---

# Hooks

Use Actions and Filters as public extension points.

Do not expose internal implementation details.

Every major workflow should provide appropriate hooks.

---

# Database

Never duplicate data unnecessarily.

Use prepared statements.

Index frequently queried fields.

Avoid N+1 queries.

Keep write operations atomic whenever practical.

---

# Metadata

Use metadata only for lightweight attributes.

Do not model relationships using metadata.

---

# Caching

Cache expensive operations.

Invalidate cache immediately after updates.

Do not cache mutable state indefinitely.

---

# Error Handling

Recoverable errors:

- Log
- Continue where possible

Critical errors:

- Stop processing
- Preserve data integrity

Never silently ignore failures.

---

# Logging

Centralize logging.

Log:

- API failures
- Import failures
- Synchronization failures
- Background job failures

Avoid excessive logging.

---

# Security

Always:

- Validate input
- Sanitize input
- Escape output
- Verify nonces
- Check capabilities
- Use prepared SQL

Never trust external data.

---

# Performance

Minimize:

- Database queries
- HTTP requests
- Memory usage

Prefer lazy loading whenever possible.

Load only what is required.

---

# Background Jobs

Long-running operations should execute asynchronously.

Jobs should be idempotent whenever possible.

Support retries for transient failures.

---

# Naming

Use clear, descriptive names.

Avoid abbreviations.

Examples:

ImportService

ImageRepository

SeasonModel

TMDBClient

Avoid names like:

Helper

Utils

Manager

Common

BaseClass

---

# Comments

Code should be self-explanatory.

Write comments only when explaining intent.

Do not describe obvious code.

---

# Constants

Avoid magic numbers.

Use named constants.

Configuration belongs in configuration classes.

---

# Global State

Avoid mutable global state.

Avoid static utility classes.

Prefer dependency injection through constructors.

---

# Testing

Design code that is easy to test.

Business logic should remain independent whenever practical.

Keep functions deterministic.

---

# Refactoring

Continuously improve the codebase.

Do not introduce breaking architectural changes without clear justification.

Prefer incremental improvements over large rewrites.

---

# Backward Compatibility

Preserve public APIs whenever possible.

Internal implementation may change without affecting consumers.

---

# Definition of Done

A feature is complete only if:

- It follows the architecture.
- It follows the data model.
- It follows WordPress best practices.
- It is secure.
- It is performant.
- It is maintainable.
- It exposes appropriate hooks where necessary.
- It introduces no unnecessary duplication.

# AI Development Rules

Before writing code:

1. Understand the requested feature.
2. Check whether similar functionality already exists.
3. Reuse existing abstractions whenever appropriate.
4. Avoid introducing duplicate logic.
5. Respect all architectural boundaries.
6. Explain any architectural trade-offs before implementing them.

When unsure:

Do not guess.

State the uncertainty and request clarification.

Never invent behavior that is not defined in the project specifications.