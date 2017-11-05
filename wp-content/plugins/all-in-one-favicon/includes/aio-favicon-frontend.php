<?php
/**
 * @package Techotronic
 * @subpackage All in one Favicon
 *
 * @since 4.0
 * @author Arne Franken
 *
 * Object that handles all actions in the WordPress frontend
 */

require_once (dirname (__FILE__) . '/favicon-render-helper.php');

class AioFaviconFrontend {

  /**
   * Constructor
   *
   * @since 3.2
   * @access public
   * @access static
   * @author Arne Franken
   *
   * @param array $aioFaviconSettings user settings
   */
  //public static function AioFaviconFrontend($aioFaviconSettings) {
  function __construct($aioFaviconSettings) {

    $this->aioFaviconSettings = $aioFaviconSettings;
    $this->faviconRenderHelper = new FaviconRenderHelper($this->aioFaviconSettings,AIOFAVICON_FRONTEND);
  }

  // AioFaviconFrontend()

  /**
   * Initialize
   *
   * @since 4.0
   * @access public
   * @author Arne Franken
   *
   * @return void
   */
  //public function init() {
  function init() {
    add_action('wp_head', array(& $this->faviconRenderHelper, 'renderFavicons'));

    //only add link to meta box
    if (isset($this->aioFaviconSettings['removeLinkFromMetaBox']) && !$this->aioFaviconSettings['removeLinkFromMetaBox']) {
      add_action('wp_meta', array(& $this, 'renderMetaLink'));
    }
  }

  //init()

  /**
   * Renders plugin link in Meta widget
   *
   * @since 1.0
   * @access public
   * @author Arne Franken
   */
  //public function renderMetaLink() {
  function renderMetaLink() {
    ?>
  <li><?php _e('Using', AIOFAVICON_TEXTDOMAIN);?>
    <a href="http://www.techotronic.de/plugins/all-in-one-favicon/" target="_blank" title="<?php echo AIOFAVICON_NAME ?>"><?php echo AIOFAVICON_NAME ?></a>
  </li>
<?php
  }

  // renderMetaLink()
}

// AioFaviconFrontend()
?>