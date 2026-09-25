CREATE VIEW newses AS SELECT
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
    mod_user.image AS user_image,
    mod_user_group.name AS group_name,
    ( SELECT count(mod_gallery.id) AS count
           FROM mod_gallery
          WHERE mod_news.id = mod_gallery.vid
          AND mod_gallery.mod_name='mod_news') AS gallery_count
   FROM mod_news
   LEFT JOIN mod_user ON mod_news.user_id = mod_user.id
   LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
   LEFT JOIN mod_news_category ON mod_news.category_id = mod_news_category.id;
