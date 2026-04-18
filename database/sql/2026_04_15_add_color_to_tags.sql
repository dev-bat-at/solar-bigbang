-- MySQL script: them cot mau cho tags va backfill mau mau neu dang dung bo seed mac dinh
-- Chay tren MySQL sau khi da chon dung database.

ALTER TABLE `tags`
    ADD COLUMN `color` VARCHAR(7) NULL AFTER `name_en`;

-- Backfill mau cho cac tag seed mau (neu co).
UPDATE `tags`
SET `color` = '#F97316'
WHERE `slug` = 'tin-cong-nghe'
  AND (`color` IS NULL OR `color` = '');

UPDATE `tags`
SET `color` = '#0EA5E9'
WHERE `slug` = 'giai-phap-he-thong'
  AND (`color` IS NULL OR `color` = '');

UPDATE `tags`
SET `color` = '#22C55E'
WHERE `slug` = 'huong-dan-su-dung'
  AND (`color` IS NULL OR `color` = '');

UPDATE `tags`
SET `color` = '#EC4899'
WHERE `slug` = 'khuyen-mai'
  AND (`color` IS NULL OR `color` = '');
