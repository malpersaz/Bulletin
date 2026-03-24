CREATE VIEW mod_publication_contents AS
SELECT
  mod_publication.id,
  mod_publication.name,
  mod_publication.created_at,
  mod_publication.status,
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
