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

  function __construct($name) {
    $this->name = $name;
  }

  public function addWarpThread($thread) {
    $this->warp[] = $thread;
  }

  public function addWeftThread($thread) {
    $this->weft[] = $thread;
  }

  public function tie($treadle, $harnesses = array()) {
    $this->treadles[$treadle] = $harnesses;
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
}
