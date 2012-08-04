-- allow admin to manage notices
INSERT INTO `acl_rule` (`type`, `role_id`, `resource`, `action`)VALUES('allow', 1, 'notice', 'manage');

CREATE TABLE notice (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, sub_title VARCHAR(255) NOT NULL, body VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, published DATETIME NOT NULL, status VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB;
CREATE TABLE notice_category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, title VARCHAR(64) NOT NULL, slug VARCHAR(128) NOT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, root INT DEFAULT NULL, UNIQUE INDEX UNIQ_5D1C5227989D9B62 (slug), INDEX IDX_5D1C5227727ACA70 (parent_id), PRIMARY KEY(id)) ENGINE = InnoDB;
ALTER TABLE notice_category ADD CONSTRAINT FK_5D1C5227727ACA70 FOREIGN KEY (parent_id) REFERENCES notice_category(id) ON DELETE SET NULL;

CREATE TABLE notice_noticecategory (notice_id INT NOT NULL, noticecategory_id INT NOT NULL, INDEX IDX_B83B5C377D540AB (notice_id), INDEX IDX_B83B5C379827FAB8 (noticecategory_id), PRIMARY KEY(notice_id, noticecategory_id)) ENGINE = InnoDB;
ALTER TABLE notice_noticecategory ADD CONSTRAINT FK_B83B5C377D540AB FOREIGN KEY (notice_id) REFERENCES notice(id) ON DELETE CASCADE;
ALTER TABLE notice_noticecategory ADD CONSTRAINT FK_B83B5C379827FAB8 FOREIGN KEY (noticecategory_id) REFERENCES notice_category(id) ON DELETE CASCADE;

-- 4.8.2012 leander
ALTER TABLE notice ADD unpublished DATETIME NOT NULL, CHANGE status status VARCHAR(2) NOT NULL;