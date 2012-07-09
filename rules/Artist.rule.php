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

        $a_search[] = "/\[(\w+) [Vv]ersion\]/";
        $a_replace[] = "[$1]";

//        $a_search[] = "/fall [oO]ut [bB]oy/";
//        $a_replace[] = "Fall out boy";

        $this->renameAllTextTags($a_search, $a_replace);

        $artist = $this->getArtist();

        $artist_search[] = "/hammerfall/";
        $artist_replace[]= "Hammerfall";

        $artist_search[] = "/SOILWORK/";
        $artist_replace[]= "Soilwork";

//        $this->setArtist(preg_replace($artist_search, $artist_replace, $artist));
//        $this->setArtist(preg_replace('/SOILWORK/', 'Soilwork', $artist));
    }
}