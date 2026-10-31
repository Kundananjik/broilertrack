ALTER TABLE sales_payments ENGINE=InnoDB;

SET @must_change_password_column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'must_change_password'
);
SET @add_must_change_password_column = IF(
    @must_change_password_column_exists = 0,
    'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active',
    'SELECT 1'
);
PREPARE add_must_change_password_column FROM @add_must_change_password_column;
EXECUTE add_must_change_password_column;
DEALLOCATE PREPARE add_must_change_password_column;

UPDATE users
SET must_change_password = 1
WHERE username = 'admin'
  AND password_hash = '$2y$10$0AhJfgp3nuHJww2yDzESSOfxQIZ6FzlfEgrj7eu0x/7aJUQoqmMc.';
