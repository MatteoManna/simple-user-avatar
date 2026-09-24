<?php
/**
 * Improved admin logic for Simple User Avatar.
 *
 * This is a cleaner and more robust version of the original admin class.
 * It keeps the same features but reduces fragile logic and improves security.
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('SimpleUserAvatar_Admin')) {

  class SimpleUserAvatar_Admin {

    private $avatar_size = 96;
    private $notice_months_expiration = 12;
    private $notices_enabled_pages = ['users.php', 'profile.php', 'user-new.php', 'user-edit.php'];
    private $donation_public_permalink = 'https://www.paypal.com/donate/?cmd=_donations&business=matteomanna87%40gmail%2ecom';
    private $reference_public_permalink = 'https://developer.wordpress.org/reference/functions/set_transient/';
    private $plugin_public_permalink = 'https://wordpress.org/plugins/simple-user-avatar/';

    public function __construct() {
      global $pagenow;

      add_action('show_user_profile', [$this, 'render_custom_user_profile_fields']);
      add_action('edit_user_profile', [$this, 'render_custom_user_profile_fields']);

      add_action('personal_options_update', [$this, 'update_custom_user_profile_fields']);
      add_action('edit_user_profile_update', [$this, 'update_custom_user_profile_fields']);

      add_action('delete_attachment', [$this, 'custom_delete_attachment']);
      add_action('admin_post_hide_notice', [$this, 'post_hide_notice']);

      if (in_array($pagenow, $this->notices_enabled_pages, true)) {
        add_action('admin_enqueue_scripts', [$this, 'custom_admin_enqueue_scripts']);
        add_action('admin_notices', [$this, 'render_admin_error_notices']);
        add_action('admin_notices', [$this, 'render_admin_donation_notice']);
      }
    }

    public static function init() {
      new self();
    }

    /**
     * Enqueue JS and CSS used in admin screens.
     */
    public function custom_admin_enqueue_scripts() {
      global $current_user;

      wp_enqueue_media();
      wp_enqueue_style('sua', plugins_url('/css/style.min.css', __FILE__), [], SUA_PLUGIN_VERSION, 'all');
      wp_enqueue_script('sua', plugins_url('/js/scripts.js', __FILE__), ['jquery'], SUA_PLUGIN_VERSION, true);

      if (!empty($current_user->user_email)) {
        $default_avatar_url = $this->get_default_avatar_url_by_email($current_user->user_email, $this->avatar_size);
        $default_avatar_url_2x = $this->get_default_avatar_url_by_email($current_user->user_email, $this->avatar_size * 2);

        wp_localize_script('sua', 'sua_obj', [
          'default_avatar_src' => $default_avatar_url,
          'default_avatar_srcset' => $default_avatar_url_2x ? $default_avatar_url_2x . ' 2x' : '',
          'input_name' => SUA_USER_META_KEY,
        ]);
      }
    }

    /**
     * Build the default avatar URL for a given email.
     */
    private function get_default_avatar_url_by_email($user_email = '', $size = 96) {
      if (empty($user_email) || !is_email($user_email)) {
        return '';
      }

      $user_email = sanitize_email($user_email);
      $md5 = md5($user_email);

      $url = add_query_arg([
        's' => absint($size),
        'd' => 'mm',
        'r' => 'g',
      ], 'https://secure.gravatar.com/avatar/' . $md5);

      return esc_url($url);
    }

    /**
     * Render custom profile avatar field.
     */
    public function render_custom_user_profile_fields($user) {
      $attachment_id = absint(get_user_meta($user->ID, SUA_USER_META_KEY, true));
      ?>
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="btn-media-add"><?php esc_html_e('Profile picture', 'simple-user-avatar'); ?></label>
            </th>
            <td>
              <?php echo get_avatar($user->ID, $this->avatar_size, '', $user->display_name, ['class' => 'sua-attachment-avatar']); ?>
              <p class="description <?php echo empty($attachment_id) ? '' : 'hidden'; ?>" id="sua-attachment-description"><?php esc_html_e("You're seeing the default profile picture.", 'simple-user-avatar'); ?></p>
              <div class="sua-btn-container">
                <button type="button" class="button" id="btn-media-add"><?php esc_html_e('Select', 'simple-user-avatar'); ?></button>
                <button type="button" class="button <?php echo empty($attachment_id) ? 'hidden' : ''; ?>" id="btn-media-remove"><?php esc_html_e('Remove', 'simple-user-avatar'); ?></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <input type="hidden" name="<?php echo esc_attr(SUA_USER_META_KEY); ?>" value="<?php echo esc_attr($attachment_id); ?>" />
      <?php
    }

    /**
     * Store the attachment ID for the current profile.
     */
    public function update_custom_user_profile_fields($user_id) {
      $user_id = absint($user_id);

      if (!$user_id) {
        return false;
      }

      if (!current_user_can('manage_options') && get_current_user_id() !== $user_id) {
        return false;
      }

      $posted_id = isset($_POST[SUA_USER_META_KEY]) ? absint(wp_unslash($_POST[SUA_USER_META_KEY])) : 0;

      delete_user_meta($user_id, SUA_USER_META_KEY);

      if (!$posted_id) {
        return true;
      }

      $attachment = get_post($posted_id);

      if (!$attachment || 'attachment' !== $attachment->post_type || 'inherit' !== $attachment->post_status && 'publish' !== $attachment->post_status) {
        return false;
      }

      add_user_meta($user_id, SUA_USER_META_KEY, $posted_id, true);

      return true;
    }

    /**
     * Remove the custom avatar reference when the attachment is deleted.
     */
    public function custom_delete_attachment($post_id) {
      $post_id = absint($post_id);

      if (!$post_id) {
        return;
      }

      $user_ids = get_users([
        'meta_key' => SUA_USER_META_KEY,
        'meta_value' => $post_id,
        'fields' => 'ids',
        'number' => 0,
      ]);

      foreach ($user_ids as $user_id) {
        delete_user_meta((int) $user_id, SUA_USER_META_KEY, $post_id);
      }
    }

    /**
     * Show admin errors.
     */
    public function render_admin_error_notices() {
      $error = isset($_GET['error']) ? sanitize_key(wp_unslash($_GET['error'])) : '';

      if (empty($error)) {
        return;
      }

      $notice = '<div class="notice notice-error is-dismissible">%s</div>';

      switch ($error) {
        case 'sua_transient_not_set':
          printf(
            $notice,
            sprintf(
              '<p>%s</p>',
              sprintf(
                /* translators: %s: URL of the website */
                __('An error occurred while <strong>saving the transient</strong>. Please make sure this website can <a href="%s" title="WordPress code reference" target="_blank" rel="noopener">save transients</a>.', 'simple-user-avatar'),
                esc_url($this->reference_public_permalink)
              )
            )
          );
          break;
      }
    }

    /**
     * Show donation notice.
     */
    public function render_admin_donation_notice() {
      global $current_user;

      $notice_is_expired = get_transient(SUA_TRANSIENT_NAME);

      if ($notice_is_expired !== false && 1 == (int) $notice_is_expired) {
        return;
      }

      $nonce_field = wp_nonce_field(SUA_TRANSIENT_NAME, '_wpnonce', true, false);
      ?>
      <div class="notice notice-info">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <p>
            <?php
            printf(
              /* translators: %1$s: User display name, %2$s: Plugin URL */
              __('Dear <strong>%1$s</strong>, thank you for using my plugin <a href="%2$s" title="Simple User Avatar" target="_blank" rel="noopener">Simple User Avatar</a>! Even a small amount, such as <strong>1$</strong> for one coffee &#x2615; will be greatly appreciated to <strong>support</strong> the development of the plugin in the future. Best regards, Matteo.', 'simple-user-avatar'),
              esc_html($current_user->display_name),
              esc_url($this->plugin_public_permalink)
            );
            ?>
          </p>
          <p>
            <a href="<?php echo esc_url($this->donation_public_permalink); ?>" class="button button-primary" target="_blank" rel="noopener"><?php esc_html_e('Donate now', 'simple-user-avatar'); ?></a>
            <button type="submit" class="button"><?php echo esc_html(sprintf(__('Hide for %d months', 'simple-user-avatar'), $this->notice_months_expiration)); ?></button>
          </p>
          <input type="hidden" name="action" value="hide_notice" />
          <?php echo $nonce_field; ?>
        </form>
      </div>
      <?php
    }

    /**
     * Save the admin dismissal of the notice.
     */
    public function post_hide_notice() {
      $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : admin_url('users.php');

      if (!empty($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), SUA_TRANSIENT_NAME)) {
        $expiration = (DAY_IN_SECONDS * 30) * $this->notice_months_expiration;

        if (set_transient(SUA_TRANSIENT_NAME, 1, $expiration) === false) {
          $redirect_url = add_query_arg('error', 'sua_transient_not_set', $redirect_url);
        }
      }

      wp_safe_redirect($redirect_url);
      exit;
    }
  }

  add_action('plugins_loaded', ['SimpleUserAvatar_Admin', 'init']);
}
