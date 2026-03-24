CREATE  VIEW settings AS SELECT
  s.*,
  u.username,
  u.name AS u_name,
  u.last_name AS u_last_name
FROM mod_settings AS s
LEFT JOIN mod_user AS u ON s.user_id = u.id;
