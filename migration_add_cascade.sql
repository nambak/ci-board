-- 외래 키에 CASCADE 설정 추가

-- 1. 기존 외래 키가 있다면 삭제 (에러 무시)
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;

-- articles 테이블의 외래 키 삭제 시도
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND TABLE_NAME = 'articles'
     AND CONSTRAINT_NAME = 'fk_articles_board'
     AND CONSTRAINT_TYPE = 'FOREIGN KEY') > 0,
    'ALTER TABLE articles DROP FOREIGN KEY fk_articles_board',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- comments 테이블의 외래 키 삭제 시도
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND TABLE_NAME = 'comments'
     AND CONSTRAINT_NAME = 'fk_comments_post'
     AND CONSTRAINT_TYPE = 'FOREIGN KEY') > 0,
    'ALTER TABLE comments DROP FOREIGN KEY fk_comments_post',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. CASCADE 옵션과 함께 외래 키 추가

-- articles 테이블: board_id에 CASCADE 설정
ALTER TABLE articles
ADD CONSTRAINT fk_articles_board
FOREIGN KEY (board_id) REFERENCES boards(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- comments 테이블: article_id에 CASCADE 설정
ALTER TABLE comments
ADD CONSTRAINT fk_comments_post
FOREIGN KEY (article_id) REFERENCES articles(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 3. 인덱스 추가 (외래 키 성능 향상)
-- board_id 인덱스
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'articles'
     AND INDEX_NAME = 'idx_articles_board_id') = 0,
    'CREATE INDEX idx_articles_board_id ON articles(board_id)',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- article_id 인덱스
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'comments'
     AND INDEX_NAME = 'idx_comments_article_id') = 0,
    'CREATE INDEX idx_comments_article_id ON comments(article_id)',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;