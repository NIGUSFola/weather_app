-- backend/db/seeds/sample_alerts.sql
-- ✅ Demo alerts for exam presentation

INSERT INTO weather_cache (city_id, type, payload, updated_at)
VALUES
((SELECT id FROM cities WHERE name='Hawassa'),'alerts',
 '[{"event":"Exam Demo Alert","description":"Heavy rain expected","start":"2025-12-22 21:00","end":"2025-12-23 06:00","sender":"Demo System"}]',
 NOW()),
((SELECT id FROM cities WHERE name='Bahir Dar'),'alerts',
 '[{"event":"Exam Demo Alert","description":"Strong winds forecast","start":"2025-12-23 09:00","end":"2025-12-23 18:00","sender":"Demo System"}]',
 NOW()),
((SELECT id FROM cities WHERE name='Shashamane'),'alerts',
 '[{"event":"Exam Demo Alert","description":"Thunderstorm risk","start":"2025-12-23 14:00","end":"2025-12-23 20:00","sender":"Demo System"}]',
 NOW());
