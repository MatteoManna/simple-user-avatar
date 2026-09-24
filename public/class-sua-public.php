<?php
/**
 * Improved version of the public avatar logic.
 *
 * This version keeps the same intent as the original plugin, but avoids fragile
 * HTML regex replacements and centralizes user resolution in a dedicated method.
 *
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('SimpleUserAvatar_Public')) {

  class SimpleUserAvatar_Public {

    /**
     * Size used for custom avatar attachments.
     *
     * @var string
     */
    private $attachment_size = 'medium';

    public static function init() {
      new self();
    }

    public function __construct() {
      add_filter('get_avatar_data', [$this, 'filter_avatar_data'], 10, 2);
    }

    /**
     * Resolve a user ID from the value passed by WordPress.
     *
     * @param mixed $id_or_email
     * @return int|false
     */
    protected function get_user_id($id_or_email) {
      if (is_numeric($id_or_email)) {
        return absint($id_or_email);
      }

      if (is_string($id_or_email) && !empty($id_or_email)) {
        $user = get_user_by('email', $id_or_email);

        if ($user && !empty($user->ID)) {
          return absint($user->ID);
        }

        return false;
      }

      if (is_object($id_or_email)) {
        if (!empty($id_or_email->ID) && is_numeric($id_or_email->ID)) {
          return absint($id_or_email->ID);
        }

        if (!empty($id_or_email->comment_author_email)) {
          $user = get_user_by('email', $id_or_email->comment_author_email);

          if ($user && !empty($user->ID)) {
            return absint($user->ID);
          }
        }
      }

      return false;
    }

    /**
     * Replace the default avatar with the custom attachment from user meta.
     *
     * @param array $args
     * @param mixed $id_or_email
     * @return array
     */
    public function filter_avatar_data($args, $id_or_email) {
      global $pagenow;

      if ($pagenow === 'options-discussion.php') {
        return $args;
      }

      $user_id = $this->get_user_id($id_or_email);

      if (!$user_id) {
        return $args;
      }

      $attachment_id = absint(get_user_meta($user_id, SUA_USER_META_KEY, true));

      if (!$attachment_id) {
        return $args;
      }

      $image = wp_get_attachment_image_src($attachment_id, $this->attachment_size);

      if ($image && !empty($image[0])) {
        $args['url'] = $image[0];
        // $args['width'] = absint($image[1]);
        // $args['height'] = absint($image[2]);
      }

      $srcset = wp_get_attachment_image_srcset($attachment_id, $this->attachment_size);

      if ($srcset) {
        $args['srcset'] = $srcset;
        $args['sizes'] = wp_get_attachment_image_sizes($attachment_id, $this->attachment_size);
      }

      return $args;
    }
  }

  add_action('plugins_loaded', ['SimpleUserAvatar_Public', 'init']);
}
