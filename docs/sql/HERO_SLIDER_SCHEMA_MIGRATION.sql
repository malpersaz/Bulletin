ALTER TABLE `mod_news`
ADD COLUMN `slider` ENUM('y', 'n') NOT NULL DEFAULT 'n' AFTER `status`;

ALTER TABLE `mod_publication`
ADD COLUMN `slider` ENUM('y', 'n') NOT NULL DEFAULT 'n' AFTER `status`;

DROP VIEW IF EXISTS `newses`;
CREATE VIEW `newses` AS
SELECT
  mod_news.id,
  mod_news.name,
  mod_news.created_at,
  mod_news.status,
  mod_news.slider,
  mod_news.content,
  mod_news.summary,
  mod_news.seo,
  mod_news.title,
  mod_news.keywords,
  mod_news.description,
  mod_news.gallery_id AS gallery_id,
  mod_news.acl_id AS acl_id,
  mod_news.deck,
  mod_news.image,
  mod_news.file_id AS file_id,
  mod_news.lang,
  mod_news.video,
  mod_news_category.name AS category_name,
  mod_news_category.seo AS category_seo,
  mod_news_category.id AS category_id,
  mod_user.username,
  mod_user.id AS user_id,
  mod_user.name AS u_name,
  mod_user.last_name AS u_last_name,
  mod_user_group.name AS group_name,
  (
    SELECT COUNT(mod_gallery.id)
    FROM mod_gallery
    WHERE mod_news.id = mod_gallery.vid
      AND mod_gallery.mod_name = 'mod_news'
  ) AS gallery_count
FROM mod_news
LEFT JOIN mod_user ON mod_news.user_id = mod_user.id
LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
LEFT JOIN mod_news_category ON mod_news.category_id = mod_news_category.id;

DROP VIEW IF EXISTS `publications`;
CREATE VIEW `publications` AS
SELECT
  mod_publication.id,
  mod_publication.name,
  mod_publication.created_at,
  mod_publication.status,
  mod_publication.slider,
  mod_publication.content,
  mod_publication.summary,
  mod_publication.seo,
  mod_publication.title,
  mod_publication.keywords,
  mod_publication.description,
  mod_publication.gallery_id AS gallery_id,
  mod_publication.acl_id AS acl_id,
  mod_publication.deck,
  mod_publication.image,
  mod_publication.file_id AS file_id,
  mod_publication.lang,
  mod_publication.video,
  mod_publication_category.name AS category_name,
  mod_publication_category.seo AS category_seo,
  mod_publication_category.id AS category_id,
  mod_user.username,
  mod_user.id AS user_id,
  mod_user.name AS u_name,
  mod_user.last_name AS u_last_name,
  mod_user.image AS user_image,
  mod_user_group.name AS group_name,
  (
    SELECT COUNT(mod_gallery.id)
    FROM mod_gallery
    WHERE mod_publication.id = mod_gallery.vid
      AND mod_gallery.mod_name = 'mod_publication'
  ) AS gallery_count
FROM mod_publication
LEFT JOIN mod_user ON mod_publication.user_id = mod_user.id
LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
LEFT JOIN mod_publication_category ON mod_publication.category_id = mod_publication_category.id;
