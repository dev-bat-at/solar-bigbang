-- MySQL/MariaDB
-- Bo sung truong notes cho bang customers de luu ghi chu gui tu API /dealers/{id}/support-requests

ALTER TABLE `customers`
    ADD COLUMN `notes` TEXT NULL AFTER `contact_time`;

-- Neu muon dong bo du lieu notes cu da luu trong lead_timelines.payload
-- thi chay them cau lenh duoi day sau khi ADD COLUMN xong.
-- Co the bo qua neu database cua ban chua co du lieu cu.

UPDATE `customers` AS `c`
INNER JOIN `leads` AS `l`
    ON `l`.`customer_id` = `c`.`id`
INNER JOIN (
    SELECT
        `lt`.`lead_id`,
        TRIM(JSON_UNQUOTE(JSON_EXTRACT(`lt`.`payload`, '$.notes'))) AS `notes`
    FROM `lead_timelines` AS `lt`
    WHERE JSON_UNQUOTE(JSON_EXTRACT(`lt`.`payload`, '$.notes')) IS NOT NULL
      AND TRIM(JSON_UNQUOTE(JSON_EXTRACT(`lt`.`payload`, '$.notes'))) <> ''
) AS `src`
    ON `src`.`lead_id` = `l`.`id`
SET
    `c`.`notes` = `src`.`notes`,
    `c`.`updated_at` = NOW()
WHERE (`c`.`notes` IS NULL OR `c`.`notes` = '');
