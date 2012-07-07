<?php

class Artist extends Rule
{
    public function trigger ( &$all_frames , $file)
    {
        $this->frames = &$all_frames;

        $a_search[] = "/\(/";
        $a_replace[] = "[";

        $a_search[] = "/\)/";
        $a_replace[] = "]";

        $a_search[] = "/[fF]eat\./";
        $a_replace[] = "ft.";

        $a_search[] = "/hammerfall/";
        $a_replace[] = "Hammerfall";

        $this->renameAllTextTags($a_search, $a_replace);
    }
}