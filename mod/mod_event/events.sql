CREATE VIEW events AS
SELECT
  mod_event.id,
  mod_event.lang,
  mod_event.category_id AS category_id,
  mod_event.name,
  mod_event.subject,
  mod_event.start_date,
  mod_event.end_date,
  mod_event.status,
  mod_event.is_official_holiday,
  mod_event.holiday_duration,
  mod_event.is_recurring,
  mod_event.slider,
  mod_event.deck,
  mod_event.summary,
  mod_event.content,
  mod_event.location,
  mod_event.participants,
  mod_event.guests,
  mod_event.seo,
  mod_event.title,
  mod_event.keywords,
  mod_event.description,
  mod_event.image,
  mod_event.gallery_id AS gallery_id,
  mod_event.file_id AS file_id,
  mod_event.video,
  mod_event.acl_id AS acl_id,
  mod_event.view_count,
  mod_event.created_at,
  mod_event.updated_at,
  mod_event.user_id AS user_id,
  mod_event_category.name AS category_name,
  mod_event_category.name AS categoryname,
  mod_event_category.seo AS category_seo,
  mod_event_category.image AS category_image,
  mod_event_category.acl_id AS category_acl_id,
  mod_user.username,
  mod_user.name AS u_name,
  mod_user.last_name AS u_last_name,
  mod_user.email,
  mod_user.image AS user_image,
  mod_user_group.name AS group_name,
  (
    SELECT COUNT(mod_gallery.id)
    FROM mod_gallery
    WHERE mod_event.id = mod_gallery.vid
      AND mod_gallery.mod_name = 'mod_event'
  ) AS gallery_count
FROM mod_event
LEFT JOIN mod_user ON mod_event.user_id = mod_user.id
LEFT JOIN mod_user_group ON mod_user.group_id = mod_user_group.group_id
LEFT JOIN mod_event_category ON mod_event.category_id = mod_event_category.id;
