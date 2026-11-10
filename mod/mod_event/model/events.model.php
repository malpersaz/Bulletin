<?php
class events_model extends ORM
{
    public $_table = "events";
    public $_pk = "id";
    public $id;
    public $lang;
    public $category_id;
    public $name;
    public $subject;
    public $start_date;
    public $end_date;
    public $status;
    public $is_official_holiday;
    public $holiday_duration;
    public $is_recurring;
    public $slider;
    public $deck;
    public $summary;
    public $content;
    public $location;
    public $participants;
    public $guests;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $image;
    public $gallery_id;
    public $file_id;
    public $video;
    public $acl_id;
    public $view_count;
    public $created_at;
    public $updated_at;
    public $user_id;
    public $category_name;
    public $categoryname;
    public $category_seo;
    public $category_image;
    public $category_acl_id;
    public $username;
    public $u_name;
    public $u_last_name;
    public $email;
    public $user_image;
    public $group_name;
    public $gallery_count;

    public function __construct()
    {
        parent::__construct();
        $this->ensureViewExists();
    }

    private function ensureViewExists()
    {
        if ($this->viewExists('events') && $this->hasUserImageColumn() && $this->hasViewCountColumn() && $this->hasCategoryAclIdColumn() && $this->hasOfficialHolidayColumn()) {
            return;
        }

        $this->dropViewIfExists('events');

        $sql = "CREATE VIEW events AS
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
LEFT JOIN mod_event_category ON mod_event.category_id = mod_event_category.id;";

        $this->query($sql);
    }

    private function hasUserImageColumn()
    {
        return $this->hasViewColumn('user_image');
    }

    private function hasViewCountColumn()
    {
        return $this->hasViewColumn('view_count');
    }

    private function hasCategoryAclIdColumn()
    {
        return $this->hasViewColumn('category_acl_id');
    }

    private function hasOfficialHolidayColumn()
    {
        return $this->hasViewColumn('is_official_holiday');
    }

    private function hasViewColumn($columnName)
    {
        try {
            if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
                $this->fetch_array("PRAGMA table_info('events')");
                if (is_array($this->_result)) {
                    foreach ($this->_result as $col) {
                        if (isset($col['name']) && $col['name'] === $columnName) {
                            return true;
                        }
                    }
                }
                return false;
            }

            $safeCol = addslashes($columnName);
            $this->fetch_array("SHOW COLUMNS FROM events LIKE '" . $safeCol . "'");
            return $this->getSize() !== 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function dropViewIfExists($viewName)
    {
        $safeView = addslashes($viewName);
        $this->query("DROP VIEW IF EXISTS " . $safeView);
    }

    private function viewExists($viewName)
    {
        $safeView = addslashes($viewName);
        if ($this->getAdapterType() && strpos($this->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->fetch_array("SELECT name FROM sqlite_master WHERE type='view' AND name='" . $safeView . "'");
            return $this->getSize() !== 0;
        }

        $this->fetch_array("SHOW FULL TABLES LIKE '" . $safeView . "'");
        return $this->getSize() !== 0;
    }
}
