cat > db/schema.sql << 'EOF'
CREATE DATABASE IF NOT EXISTS paddington
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE paddington;

CREATE TABLE IF NOT EXISTS waitlist (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title            ENUM('Mrs.','Mr.','Miss') NOT NULL DEFAULT '',
  first_name       VARCHAR(100)    NOT NULL,
  last_name        VARCHAR(100)    NOT NULL,
  phone            VARCHAR(50)     NOT NULL,
  email            VARCHAR(100)    NOT NULL,
  preferred_colors VARCHAR(255)    NOT NULL,
  created_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);
EOF
