-- backend/db/migrations/002_add_indexes.sql
-- Add indexes to improve query performance

-- Weather cache lookups by user and city
CREATE INDEX idx_weather_cache_user ON weather_cache(user_id);
CREATE INDEX idx_weather_cache_city ON weather_cache(city_id);
CREATE INDEX idx_weather_cache_type ON weather_cache(type);

-- API requests lookups by user
CREATE INDEX idx_api_requests_user ON api_requests(user_id);

-- Logs lookups by user and level
CREATE INDEX idx_logs_user ON logs(user_id);
CREATE INDEX idx_logs_level ON logs(level);
