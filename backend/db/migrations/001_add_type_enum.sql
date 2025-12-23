-- backend/db/migrations/001_add_type_enum.sql
-- Ensure weather_cache has a type ENUM column for cache categories

ALTER TABLE weather_cache
ADD COLUMN type ENUM('forecast','alerts','current','radar') NOT NULL
AFTER city_id;
