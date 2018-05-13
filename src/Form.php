<?php
class Form {
  public static function csrf() {
    ?>
    <input type="hidden" name="csrf_token" value="<?php echo Auth::getCsrfToken(); ?>" />
    <?php
  }
}