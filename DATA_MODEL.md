# DATA_MODEL.md

## Purpose
Canonical data model for TMDB Importer.

## Storage Strategy

| Entity | Storage |
|---------|----------|
| Movie | WordPress |
| TV Show | WordPress |
| Genre | Taxonomy |
| Release Year | Taxonomy |
| Keyword | Taxonomy |
| People | Taxonomy |
| Network | Taxonomy |
| Production Company | Taxonomy |
| Season | Custom Table |
| Episode | Custom Table |
| Person Relationship | Custom Table |
| Links | Custom Table |
| Import Job | Custom Table |
| Sync Job | Custom Table |

## Movies
Stored in WordPress.

Relationships:
- Genres
- Release Year
- Keywords
- People
- Companies

## TV Shows
Stored in WordPress.

Relationships:
- Genres
- Release Year
- Keywords
- Networks
- People
- Seasons

## Seasons
Custom table.

Fields:
- id
- tmdb_id
- show_id
- season_number
- name
- overview
- poster
- air_date
- episode_count

## Episodes
Custom table.

Fields:
- id
- tmdb_id
- season_id
- episode_number
- name
- overview
- runtime
- air_date
- still_image

## People
Stored as Taxonomy.

Metadata:
- TMDB ID
- Profile image
- Biography
- Homepage

## Person Relationship
Custom table.

Fields:
- id
- person_term_id
- object_type
- object_id
- role
- character_name
- department
- credit_order

## Links
Custom table.

Fields:
- object_type
- object_id
- server
- language
- quality
- url

## Identity
TMDB ID is the canonical unique identifier.

## Referential Integrity
Deleting a TV Show removes:
- Seasons
- Episodes
- Episode links

Deleting a Season removes:
- Episodes
- Episode links

Shared taxonomies remain.

## Indexing
Index:
- id
- tmdb_id
- parent ids
- lookup fields

## Future
Supports:
- Collections
- Watch Providers
- Certifications
- Guest Cast
- Multi-language metadata
