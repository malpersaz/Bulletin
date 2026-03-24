CREATE OR REPLACE VIEW resimler AS SELECT
  mod_pictures.id AS id,
  mod_pictures.name AS name,
  mod_pictures.url AS url,
  mod_imagecategories.name AS kat_ad,
  mod_imagecategories.seo AS kat_seo,
  mod_imagecategories.id AS category_id,
  mod_pictures.zaman AS created_at,
  mod_imagecategories.lang AS lang,
  mod_imagecategories.root AS root,
  mod_imagecategories.title AS title,
  mod_imagecategories.keywords AS keywords,
  mod_imagecategories.description AS description
FROM mod_pictures LEFT JOIN mod_imagecategories
  ON mod_pictures.category_id=mod_imagecategories.id