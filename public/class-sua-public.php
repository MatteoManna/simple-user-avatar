<?php
// Injection prevention
if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('SimpleUserAvatar_Public')) {

  /**
   * PHP class SimpleUserAvatar_Public
   *
   * @since 2.8
   */
  class SimpleUserAvatar_Public {

    public function __construct() {

      // Override the avatar URL. This covers get_avatar(), get_avatar_url() and
      // get_avatar_data(), which all resolve their URL through get_avatar_data().
      add_filter('get_avatar_data', [$this, 'get_avatar_data_filter'], 5, 2);

    }


    public static function init() {

      new self;

    }


    /**
     * Override the avatar URL with the user's uploaded image.
     *
     * @since  1.0
     * @return array
     */
    public function get_avatar_data_filter($args, $id_or_email) {

      // Global $pagenow
      global $pagenow;

      // If pagenow is "Discussion" in WP Admin return default avatar
      if ($pagenow === 'options-discussion.php') {
        return $args;
      }

      // Get user ID from the ID, email or comment/user object
      if (is_numeric($id_or_email)) {
        $user_id = (int)$id_or_email;
      } elseif (is_object($id_or_email) && isset($id_or_email->ID)) {
        $user_id = (int)$id_or_email->ID;
      } elseif (is_object($id_or_email) && isset($id_or_email->comment_author_email)) {
        $user = get_user_by('email', $id_or_email->comment_author_email);
        $user_id = isset($user->ID) ? (int)$user->ID : 0;
      } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        $user_id = isset($user->ID) ? (int)$user->ID : 0;
      } else {
        $user_id = 0;
      }

      // Get attachment ID from user meta
      $attachment_id = get_user_meta($user_id, SUA_USER_META_KEY, true);
      if (empty($attachment_id) || !is_numeric($attachment_id)) {
        return $args;
      }

      // Override WordPress URL with the uploaded image
      $attachment_src = wp_get_attachment_image_src($attachment_id, [$args['size'], $args['size']]);
      if ($attachment_src !== false) {
        $args['url'] = $attachment_src[0];
      }

      return $args;

    }

  }

  add_action('plugins_loaded', ['SimpleUserAvatar_Public', 'init']);

}
