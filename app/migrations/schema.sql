CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  telegram_id BIGINT UNSIGNED NOT NULL UNIQUE,
  username VARCHAR(64) NULL,
  first_name VARCHAR(128) NULL,
  last_name VARCHAR(128) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS balances (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  available DECIMAL(18,8) NOT NULL DEFAULT 0,
  reserved DECIMAL(18,8) NOT NULL DEFAULT 0,
  CONSTRAINT fk_balances_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  symbol VARCHAR(32) NOT NULL,
  chain VARCHAR(64) NOT NULL,
  expected_listing_at DATETIME NULL,
  risk_level ENUM('LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'MEDIUM',
  description TEXT NULL,
  source VARCHAR(64) NOT NULL DEFAULT 'manual',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(128) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_items (
  collection_id BIGINT UNSIGNED NOT NULL,
  token_id BIGINT UNSIGNED NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  PRIMARY KEY (collection_id, token_id),
  CONSTRAINT fk_ci_collection FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_token FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  token_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, token_id),
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorites_token FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deposits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  amount_usdt DECIMAL(18,8) NOT NULL,
  method VARCHAR(64) NOT NULL,
  status ENUM('PENDING','PAID','REJECTED') NOT NULL DEFAULT 'PENDING',
  external_id VARCHAR(128) NULL,
  pay_url VARCHAR(512) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  UNIQUE KEY uq_deposits_external_id (external_id),
  CONSTRAINT fk_deposits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('NEW','CONFIRMED','EXECUTED','DISTRIBUTED','CANCELED','REFUNDED') NOT NULL DEFAULT 'NEW',
  total_amount_usdt DECIMAL(18,8) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  token_id BIGINT UNSIGNED NOT NULL,
  amount_usdt DECIMAL(18,8) NOT NULL,
  price_ref DECIMAL(18,8) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_token FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS executions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_item_id BIGINT UNSIGNED NOT NULL,
  executed_at DATETIME NOT NULL,
  execution_price DECIMAL(18,8) NOT NULL,
  token_amount DECIMAL(18,8) NOT NULL,
  admin_notes TEXT NULL,
  CONSTRAINT fk_exec_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS holdings (
  user_id BIGINT UNSIGNED NOT NULL,
  token_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(18,8) NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, token_id),
  CONSTRAINT fk_holdings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_holdings_token FOREIGN KEY (token_id) REFERENCES tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor VARCHAR(128) NOT NULL,
  action VARCHAR(128) NOT NULL,
  payload_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(64) NOT NULL,
  endpoint VARCHAR(128) NOT NULL,
  hits INT NOT NULL DEFAULT 0,
  window_start INT NOT NULL,
  UNIQUE KEY uq_rate_limits (ip, endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tokens (name, symbol, chain, expected_listing_at, risk_level, description, source, is_active)
VALUES
('NovaX', 'NVX', 'Ethereum', DATE_ADD(NOW(), INTERVAL 14 DAY), 'MEDIUM', 'AI infra token with upcoming CEX listing.', 'manual', 1),
('Orbit Launch', 'ORBL', 'BSC', DATE_ADD(NOW(), INTERVAL 30 DAY), 'HIGH', 'Early-stage gaming token before public listing.', 'manual', 1),
('LayerFox', 'LFOX', 'Arbitrum', DATE_ADD(NOW(), INTERVAL 21 DAY), 'MEDIUM', 'L2 analytics platform token.', 'manual', 1),
('MetaSeed', 'MSEED', 'Polygon', DATE_ADD(NOW(), INTERVAL 11 DAY), 'HIGH', 'GameFi token with seed allocation.', 'manual', 1),
('QuantumPay', 'QPAY', 'Solana', DATE_ADD(NOW(), INTERVAL 18 DAY), 'MEDIUM', 'Payments rail for onchain merchants.', 'manual', 1),
('DriftX', 'DRFX', 'Base', DATE_ADD(NOW(), INTERVAL 40 DAY), 'HIGH', 'Derivative infra before exchange listing.', 'manual', 1),
('AsterNet', 'ASTR', 'Avalanche', DATE_ADD(NOW(), INTERVAL 26 DAY), 'LOW', 'Infrastructure and staking utility token.', 'manual', 1),
('HydraLink', 'HYDL', 'Ethereum', DATE_ADD(NOW(), INTERVAL 35 DAY), 'MEDIUM', 'Cross-chain bridge liquidity token.', 'manual', 1),
('PulseNode', 'PLSN', 'BSC', DATE_ADD(NOW(), INTERVAL 9 DAY), 'HIGH', 'Node rental ecosystem token.', 'manual', 1),
('Orbital AI', 'ORAI', 'Arbitrum', DATE_ADD(NOW(), INTERVAL 28 DAY), 'MEDIUM', 'AI compute marketplace token.', 'manual', 1),
('BlueMint', 'BLMT', 'Polygon', DATE_ADD(NOW(), INTERVAL 16 DAY), 'LOW', 'NFT infra token with utility burns.', 'manual', 1),
('VectorEx', 'VTRX', 'Solana', DATE_ADD(NOW(), INTERVAL 22 DAY), 'HIGH', 'Order routing / DEX infra token.', 'manual', 1);

INSERT INTO collections (title, description, is_active)
VALUES
('Early Picks', 'Топ идеи по ранним листингам', 1),
('Diversified Basket', '10 тестовых тикеров для резерва', 1);

INSERT INTO collection_items (collection_id, token_id, sort)
VALUES
(1, 1, 1), (1, 2, 2), (1, 3, 3), (1, 5, 4),
(2, 3, 1), (2, 4, 2), (2, 5, 3), (2, 6, 4), (2, 7, 5),
(2, 8, 6), (2, 9, 7), (2, 10, 8), (2, 11, 9), (2, 12, 10);
