<?php
/**
 *
 * @package Techotronic
 * @subpackage All in one Favicon
 *
 * @since 4.0
 * @author Arne Franken
 *
 * Help render uploaded Favicons
 */
class FaviconRenderHelper {

  /**
   * Constructor
   *
   * @since 4.1
   * @access public
   * @access static
   * @author Arne Franken
   *
   * @param Array $aioFaviconSettings user settings
   * @param String $aioFaviconType either 'frontend' or 'backend'
   */
  //public static function FaviconRenderHelper($aioFaviconSettings, $aioFaviconType) {
  function __construct($aioFaviconSettings, $aioFaviconType) {

    $this->aioFaviconSettings = $aioFaviconSettings;
    $this->aioFaviconType = $aioFaviconType;

  }

  // FaviconRenderHelper()

  /**
   * Renders all different configured Favicons
   *
   * @since 4.1
   * @access public
   * @author Arne Franken
   */
  //public function renderFavicons() {
  function renderFavicons() {
    if (!empty($this->aioFaviconSettings)) {
      //add comment to HTML
      echo '<!-- '.AIOFAVICON_NAME.' '. AIOFAVICON_VERSION.' -->';

      foreach ((array)$this->aioFaviconSettings as $favicon => $url) {
        if (!empty($url)) {

          if (preg_match('/' . $this->aioFaviconType . '/i', $favicon)) {

            if (preg_match('/ico/i', $favicon)) {

              $this->render('shortcut icon',$url,null);
            }
            else if (preg_match('/gif/i', $favicon)) {

              $this->render('icon',$url,'image/gif');
            }
            else if (preg_match('/png/i', $favicon)) {

              $this->render('icon',$url,'image/png');
            }
            else if (preg_match('/apple/i', $favicon)) {

              if ((isset($this->aioFaviconSettings['removeReflectiveShine'])
                  && !$this->aioFaviconSettings['removeReflectiveShine'])) {

                $this->render('apple-touch-icon',$url,null);
              }
              else {

                $this->render('apple-touch-icon-precomposed',$url,null);
              }
            }
          }
        }
      }
    }
  }

  // renderFavicons()

  /**
   * Render <link> tag to HTML
   *
   * @since 4.1
   * @access private
   * @author Arne Franken
   *
   * @param $rel String value for the rel attribute
   * @param $url String value for the href attribute
   * @param $type String value for the type attribute. May be null.
   */
  //private function render($rel, $url, $type) {
  function render($rel, $url, $type) {

  ?><link rel="<?php echo $rel?>" href="<?php echo htmlspecialchars($url)?>" <?php if(!empty($type)) { echo 'type="' . $type . '"'; } ?>/>
<?php
  }

  // render()

}

// FaviconRenderHelper