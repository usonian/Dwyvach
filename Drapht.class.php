<?php

define('DRAPHT_PATTERN_TABBY', 1);
define('DRAPHT_PATTERN_TWILL', 2);
define('DRAPHT_PATTERN_CUSTOM', 3);

/**
 * PHP class for building and rendering weaving drafts.
 *
 * @author Andy Chase <andychase@gmail.com>
 */
class Drapht {
  public $name;
  public $warp = array();
  public $weft = array();
  public $treadles = array();
  public $outputDir = '.';
  public $colors = array();

  function __construct($name) {
    $this->name = $name;
  }

  public function addWarpThread($thread) {
    $this->warp[count($this->warp)] = $thread;
  }

  public function addWeftThread($thread) {
    $this->weft[count($this->weft)] = $thread;
  }

  public function tie($treadle, $harnesses = array()) {
    $this->treadles[$treadle] = $harnesses;
  }

  public function setWarpPattern($harnesses) {
    //Set warp harnesses
    $h = 0;
    for ($i = 0; $i < count($this->warp); $i++) {
      $this->warp[$i]->setHarness($harnesses[$h]);
      $h++;
      if ($h == count($harnesses)) {
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
      case DRAPHT_PATTERN_TABBY:
        $pattern = array(
          'warp' => array(1,2),
          'weft' => array(1,2),
          'tie'  => array(
            1 => array(1),
            2 => array(2),
          )
        );
        break;
      case DRAPHT_PATTERN_TWILL:
        $pattern = array(
          'warp' => array(1,2,3,4),
          'weft' => array(1,2,3,4),
          'tie' => array(
            1 => array(1,2),
            2 => array(2,3),
            3 => array(3,4),
            4 => array(4,1),
          ),
        );
        break;
      case DRAPHT_PATTERN_CUSTOM:
        $pattern = $custom;
    }

    $this->setWarpPattern($pattern['warp']);
    $this->setWeftPattern($pattern['weft']);

    //Tie-ups
    foreach ($pattern['tie'] as $treadle => $harnesses) {
      $this->tie($treadle, $harnesses);
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
      $warp[] = $this->warp[$i]->harness;
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
   * Render this draft as a PNG graphic.
   * @param string $filename
   */
  public function render($filename = null) {
    if (empty($filename)) {
      $filename = $this->name .'.png';
    }
    $width = count($this->warp);
    $height = count($this->weft);

    $img = imagecreate($width, $height);

    for ($y = 0; $y < $height; $y++) {
      $weft = $this->weft[$y];
      //Allocate color if necessary
      if (empty($this->colors[$weft->color->hex])) {
        $this->allocateColor($img, $weft->color);
      }
      //Draw solid weft line
      imageline($img, 0, $y, $width - 1, $y, $this->colors[$weft->color->hex]);

      //Get the harnesses lifted for this weft
      $harnesses = $this->treadles[$weft->treadle];

      for ($x = 0; $x < $width; $x++) {
        $warp = $this->warp[$x];
        if (in_array($warp->harness, $harnesses)) {
          //Draw pixel in warp color (allocate if necessary
          if (empty($this->colors[$warp->color->hex])) {
            $this->allocateColor($img, $warp->color);
          }
          imagesetpixel($img, $x, $y, $this->colors[$warp->color->hex]);
        }
      }
    }
    imagepng($img, $this->outputDir .'/'. $filename);
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
  //public $harness;
  public $harness;

  function __construct(&$color, $harness = NULL) {
    $this->color = $color;
    if ($harness) {
      $this->setHarness($harness);
    }
  }

  function setHarness($newHarness) {
    $this->harness = $newHarness;
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

  function __construct(&$color, $treadle = NULL) {
    $this->color = $color;
    if ($treadle) {
      $this->setTreadle($treadle);
    }
  }

  function setTreadle($newTreadle) {
    $this->treadle = $newTreadle;
  }
}

