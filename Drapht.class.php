<?php

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

  function __construct(&$color, $harness) {
    $this->color = $color;
    $this->harness = $harness;
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

  function __construct(&$color, $treadle) {
    $this->color = $color;
    $this->treadle = $treadle;
  }

  function setTreadle($newTreadle) {
    $this->treadle = $newTreadle;
  }
}

