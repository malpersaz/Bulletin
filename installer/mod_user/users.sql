CREATE OR REPLACE VIEW users AS SELECT
        u.id AS id,
        u.username AS username,
        u.updated_at AS updated_at,
        u.created_at AS created_at,
        u.ip AS ip,
        u.image AS image,
        u.city AS city,
        u.phone AS phone,
        u.email AS email,
        u.name AS name,
        u.last_name AS last_name,
        u.status AS status,
        u.group_id AS group_id,
        g.name AS group_name
FROM mod_user u
        LEFT JOIN mod_user_group g
        ON u.group_id=g.group_id
