<?php

/**
 * Dwyvach is a set of PHP classes for building and rendering weaving drafts.
 *
 * (c) 2010 Andy Chase <andychase@gmail.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

define('DWYVACH_PATTERN_TABBY', 1);
define('DWYVACH_PATTERN_TWILL', 2);
define('DWYVACH_PATTERN_CUSTOM', 3);
define('DWYVACH_WARP', 4);
define('DWYVACH_WEFT', 5);

/**
 * PHP class for building and rendering weaving drafts.
 *
 * @author Andy Chase <andychase@gmail.com>
 */
class Dwyvach {
  public $name;
  public $warp = array();
  public $weft = array();
  public $treadles = array();
  public $outputDir = '.';
  public $colors = array();
  public $drawDown = array();
  public $gdImage = null;

  function __construct($name) {
    $this->name = $name;
  }

  public function addWarpThread($thread) {
    $this->warp[count($this->warp)] = $thread;
  }

  /**
   * Add multiple threads to the warp. If $pattern is empty, the shaft
   * threading pattern will need to be set later.
   *
   * @param <type> $color
   * @param <type> $width
   */
  public function addWarpStripe($color, $width, $pattern = null) {
    if ($pattern) {
      $h = 0;
      for ($i = 0; $i < $width; $i++) {
        $this->addWarpThread(new WarpThread($color, $pattern[$h]));
        $h++;
        if ($h == count($pattern)) {
          $h = 0;
        }
      }
    }
    else {
      for ($i = 0; $i < $width; $i++) {
        $this->addWarpThread(new WarpThread($color));
      }
    }
  }

  public function addWeftThread($thread) {
    $this->weft[count($this->weft)] = $thread;
  }

  /**
   * Add multiple threads to the weft. If $pattern is empty, the weft
   * treadle pattern will need to be set later.
   *
   * @param <type> $color
   * @param <type> $width
   */
  public function addWeftStripe($color, $width, $pattern = null) {
    if ($pattern) {
      $t = 0;
      for ($i = 0; $i < $width; $i++) {
        $this->addWeftThread(new WeftThread($color, $pattern[$t]));
        $t++;
        if ($t == count($pattern)) {
          $t = 0;
        }
      }
    }
    else {
      for ($i = 0; $i < $width; $i++) {
        $this->addWeftThread(new WeftThread($color));
      }
    }
  }

  public function tie($treadle, $shafts = array()) {
    $this->treadles[$treadle] = $shafts;
  }

  public function setWarpPattern($shafts) {
    //Set warp shafts
    $h = 0;
    for ($i = 0; $i < count($this->warp); $i++) {
      $this->warp[$i]->setshaft($shafts[$h]);
      $h++;
      if ($h == count($shafts)) {
        $h = 0;
      }
    }
  }

  public function setWeftPattern($treadles) {
    //Set weft treadles
    $t = 0;
    for ($i = 0; $i < count($this->weft); $i++) {
      $this->weft[$i]->setTreadle($treadles[$t]);
      $t++;
      if ($t == count($treadles)) {
        $t = 0;
      }
    }
  }

  public function setPattern($type, $custom = NULL) {
    switch($type) {
      case DWYVACH_PATTERN_TABBY:
        $pattern = array(
          'warp' => array(1,2),
          'weft' => array(1,2),
          'tie'  => array(
            1 => array(1),
            2 => array(2),
          )
        );
        break;
      case DWYVACH_PATTERN_TWILL:
        $pattern = array(
          'warp' => array(1,2,3,4),
          'weft' => array(4,3,2,1),
          'tie' => array(
            1 => array(1,2),
            2 => array(2,3),
            3 => array(3,4),
            4 => array(4,1),
          ),
        );
        break;
      case DWYVACH_PATTERN_CUSTOM:
        $pattern = $custom;
    }

    $this->setWarpPattern($pattern['warp']);
    $this->setWeftPattern($pattern['weft']);

    //Tie-ups
    foreach ($pattern['tie'] as $treadle => $shafts) {
      $this->tie($treadle, $shafts);
    }
  }

  /**
   * Returns an array representing the warp, weft, and treddle tie-up patterns
   * used by this draft, in the same format used by the setPattern() method; can
   * be used to export/import patterns.
   * @return <type>
   */
  public function getPattern() {
    $warp = array();
    $weft = array();
    for ($i = 0; $i < count($this->warp); $i++) {
      $warp[] = $this->warp[$i]->shaft;
    }
    for ($i = 0; $i < count($this->weft); $i++) {
      $weft[] = $this->weft[$i]->treadle;
    }
    return array(
      'warp' => $warp,
      'weft' => $weft,
      'tie' => $this->treadles,
    );
  }

  /**
   * Based on the currently configured draft, builds a two-dimensional array
   * representing the draft drawdown.
   */
  public function buildDrawDown() {
    $dd = array();

    $width = count($this->warp);
    $height = count($this->weft);

    for ($y = 0; $y < $height; $y++) {
      $weft = $this->weft[$y];

      //Get the shafts lifted for this weft
      $shafts = $this->treadles[$weft->treadle];
      for ($x = 0; $x < $width; $x++) {

        $warp = $this->warp[$x];
        $ddX = $width - $x - 1;
        if (is_array($shafts) && in_array($warp->shaft, $shafts)) {
          //Set cell as warp
          $dd[$ddX][$y] = array('type' => DWYVACH_WARP, 'color' => $warp->color, 'x' => $x, 'y' => $y);
        }
        else {
          //Set cell as weft
          $dd[$ddX][$y] = array('type' => DWYVACH_WEFT, 'color' => $weft->color, 'x' => $x, 'y' => $y);
        }
      }
    }
    $this->drawDown = $dd;
  }

  /**
   * Returns a representation of the Drawdown in JSON format.
   * @return string
   */
  public function renderJson($imagePath = null) {
    $this->buildDrawDown();
    //Collapse into a single array where each element contains x,y,warp/weft,
    //and color info; easier to deal with than a set of nested objects
    $width = count($this->warp);
    $height = count($this->weft);
    $drawdown = array();
    $json = array();
    for ($y = 0; $y < $height; $y++) {
      for ($x = 0; $x < $width; $x++) {
        $ddX = $width - $x - 1;
        $pixel = $this->drawDown[$x][$y];
        $colorHex = $pixel['color']->hex;
        $json['drawdown'][] = array(
          'type' => $pixel['type'],
          'color' => '#'. $colorHex,
          'x' => $ddX,
          'y' => $y,
        );
      }
    }

    if ($imagePath != null) {
      $data = base64_encode(file_get_contents($imagePath));
      $img = "<strong>Preview:</strong><br/><br/><img src=\"data:image/png;base64,$data\"/>";
      $json['image'] = $img;
    }

    return json_encode($json);
  }

  /**
   * Create a GD image resource from the currently defined warp, weft, and
   * treadle configuration.
   *
   * @param int $scale
   */
  public function buildGd($scale = 100) {

    $this->buildDrawDown();
    $width = count($this->warp);
    $height = count($this->weft);
    $img = imagecreate($width, $height);
    for ($y = 0; $y < $height; $y++) {
      for ($x = 0; $x < $width; $x++) {
        $pixel = $this->drawDown[$x][$y];
        $colorHex = $pixel['color']->hex;
        if (empty($this->colors[$colorHex])) {
          $this->allocateColor($img, $pixel['color']);
        }
        imagesetpixel($img, $x, $y, $this->colors[$colorHex]);
      }
    }

    $scale = $scale / 100;

    if ($scale != 100) {
      $width = round(count($this->warp) * $scale);
      $height = round(count($this->weft) * $scale);
      $scaled_img = imagecreatetruecolor($width, $height);
      imagecopyresampled($scaled_img, $img, 0, 0, 0, 0, $width, $height, count($this->warp), count($this->weft));
      $this->gdImage = $scaled_img;
    }
    else {
      $this->gdImage = $img;
    }
  }

  /**
   * Render this draft as a PNG graphic.
   *
   * @param string $filename
   * @param $scale
   *   Scale at which the image should be rendered, in percent. (For best results
   *   use multiples of 100.)
   *
   */
  public function render($filename = null, $scale = 100) {
    if (empty($filename)) {
      $filename = $this->name .'.png';
    }
    $this->buildGd($scale);
    imagepng($this->gdImage, $this->outputDir .'/'. $filename);
  }

  /**
   * Returns a plain-text representation of the drawdown
   * @param char $warp
   * @param char $weft
   * @return string
   */
  public function renderText($warp = '|', $weft = '-') {
    $drawdown = '';
    $this->buildDrawDown();
    $width = count($this->warp);
    $height = count($this->weft);
    for ($y = 0; $y < $height; $y++) {
      for ($x = 0; $x < $width; $x++) {
        $drawdown .= $this->drawDown[$x][$y]['type'] == DWYVACH_WARP ? $warp : $weft;
      }
      $drawdown .= "\n";
    }
    return $drawdown;
  }

  /**
   * Render this draft as a tiled PNG graphic.
   *
   * @param int $repeatX
   * @param int $repeatY
   * @param string $filename
   * @param int $scale
   */
  public function renderTiled($repeatX = 3, $repeatY = 3, $filename = null, $scale = 100) {
    if (empty($filename)) {
      $filename = $this->name .'_tiled.png';
    }
    $this->buildGd($scale);

    //Calculate tiled image width & height
    $gdWidth = imagesx($this->gdImage);
    $gdHeight = imagesy($this->gdImage);

    $tiledWidth = $gdWidth * $repeatX;
    $tiledHeight = $gdHeight * $repeatY;

    $tiled = imagecreatetruecolor($tiledWidth, $tiledHeight);
    for ($x = 0; $x < $repeatX; $x++) {
      for ($y = 0; $y < $repeatY; $y++) {
        $dst_x = $x * $gdWidth;
        $dst_y = $y * $gdHeight;
        $src_x = 0;
        $src_y = 0;
        $dst_w = $gdWidth;
        $dst_h = $gdHeight;
        $src_w = $gdWidth;
        $src_h = $gdHeight;
        imagecopyresampled($tiled, $this->gdImage, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
      }
    }
    imagepng($tiled, $this->outputDir .'/'. $filename);
  }

  /**
   *
   * @param object $image GD image handler
   * @param int $r Red
   * @param int $g Green
   * @param int $b Blue
   */
  private function allocateColor(&$image, $colorChip) {
    $this->colors[$colorChip->hex] = imagecolorallocate($image, $colorChip->r, $colorChip->g, $colorChip->b);
  }
}

/**
 * PHP class representing a warp thread
 */
class WarpThread {
  //(Reference to a ColorChip object)
  public $color;
  //public $shaft;
  public $shaft;

  function __construct(&$color, $shaft = NULL) {
    $this->color = $color;
    if ($shaft) {
      $this->setShaft($shaft);
    }
    else {
      $this->setShaft(0);
    }
  }

  function setShaft($newShaft) {
    $this->shaft = $newShaft;
  }

  function setColor($newColor) {
    $this->color = $newColor;
  }
}

/**
 * PHP class representing a weft thread
     */
class WeftThread {
  //Reference to a ColorChip object
  public $color;
  //The treadle(s) to be used on this weft
  public $treadle;

  function __construct(&$color, $treadle = 0) {
    $this->color = $color;
    if ($treadle) {
      $this->setTreadle($treadle);
    }
    else {
      $this->setTreadle(0);
    }
  }

  function setTreadle($newTreadle) {
    $this->treadle = $newTreadle;
  }

  function setColor($newColor) {
    $this->color = $newColor;
  }
}

class DwyvachTartan extends Dwyvach {
  
  public $notation;
  public $multiplier;
  
  // Black
  const COLOR_K  = '000000';
  const COLOR_BL = '000000';
  
  // Blue
  const COLOR_B  = '336699';
  
  // Red
  const COLOR_R  = '990000';
  
  // White
  const COLOR_W  = 'FFFFFF';
  
  // Yellow (Gold)
  const COLOR_Y  = 'CCCC00';
  const COLOR_GO = 'CCCC00';
  
  // Green
  const COLOR_G  = '009900';
  
  // Grey
  const COLOR_GR = '999999';
  const COLOR_N  = '999999';
  
  // Purple
  const COLOR_P  = '660099';
  
  // Azure
  const COLOR_A  = '9999DD';
  const COLOR_AA = '9999DD';
  
  function __construct($name, $notation, $pivot = TRUE, $multiplier = 1) {
    parent::__construct($name);
    
    //Strip whitespace from pattern string
    $pattern = preg_replace('/\s/', '', $notation);
    
    $this->notation = $notation;
    $this->multiplier = $multiplier;
    $this->parseNotation($pivot);
    $this->setPattern(DWYVACH_PATTERN_TWILL);    
  }
  
  /**
   * Given the stripe pattern passed to the constructor, parse it and build 
   * the corresponding warp and weft stripes
   */
  private function parseNotation($pivot) {

    //Split the pattern by alternating letter/number groups

    // Regex will find groupings with one or two letters followed by one to 
    // three numbers; this should be sufficient for all reasonable tartan
    // definitions.
    $regex = '/([A-Za-z]{1,2})([0-9]{1,3})/';

    $matches = array();
    
    preg_match_all($regex, $this->notation, $matches, PREG_SET_ORDER);

    // $matches now contains stripes in the following structure:
    //  Array
    //  (
    //      [0] => Array
    //          (
    //              [0] => BL4
    //              [1] => BL
    //              [2] => 4
    //          )
    //
    //      [1] => Array
    //          (
    //              [0] => R4
    //              [1] => R
    //              [2] => 4
    //          )
    //          
    //      etc...
    //  )
    $this->addStripes($matches);
    if ($pivot) {
      // Tartan pivots on the first and last stripes; throw them away, reverse
      // the rest of the pattern and add it:
      array_shift($matches);
      array_pop($matches);
      $matches = array_reverse($matches);
      $this->addStripes($matches);
    }
  }
  
  private function addStripes($stripes) {
    foreach ($stripes as $stripe) {
      $colorHex = constant('DwyvachTartan::COLOR_'.$stripe[1]);
      $color = new ColorChip($colorHex, NULL, NULL, CC_HEX);
      $this->addWarpStripe($color, ($stripe[2] * $this->multiplier));
      $this->addWeftStripe($color, ($stripe[2] * $this->multiplier));
    }      
  }
}