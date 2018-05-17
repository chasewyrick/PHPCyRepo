<?php
class HTML {
  public static function csrf() {
    ?>
    <input type="hidden" name="csrf_token" value="<?php echo Framework::$auth->getCsrfToken(); ?>" />
    <?php
  }
}