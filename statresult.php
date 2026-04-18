<?php
class StatResult
{
	public $time;
	public $current_score;
	public $current_sd;
	public $best_score;
	public $name;
	public function __toString() 
		{
		return $this->name."|".$this->time."|".$this->current_score."|".$this->current_sd."|".$this->best_score;
		}
}

?>