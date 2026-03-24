CREATE VIEW pictures AS SELECT
mod_imagecategories.id AS id,
mod_imagecategories.name AS name,
mod_imagecategories.image AS image,
mod_imagecategories.content AS content,
mod_imagecategories.lang AS lang,
mod_imagecategories.parent_id AS parent_id,
mod_imagecategories.order AS `order`,
mod_imagecategories.root AS root,
mod_imagecategories.seo AS seo,
mod_imagecategories.title AS title,
mod_imagecategories.keywords AS keywords,
mod_imagecategories.description AS description,
mod_imagecategories.acl_id AS acl_id,
mod_imagecategories.user_id AS user_id,
mod_gallery.name AS gallery_name,
mod_gallery.url AS gallery_url,
mod_gallery.created_at AS gallery_created_at,
mod_gallery.vid AS vid
FROM mod_imagecategories
LEFT JOIN mod_gallery ON mod_imagecategories.id=mod_gallery.vid
WHERE mod_gallery.mod_name='mod_imagecategories'