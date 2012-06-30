<?php

class Artist extends Rule
{
    public function trigger ( &$all_frames )
    {
        $this->frames = &$all_frames;

        $a_search[] = "/alien ant farm/";
        $a_replace[] = "Alien Ant Farm";
        $this->setArtist( preg_replace($a_search, $a_replace, $this->getArtist()));

//        $search[] = "/\((.+)\)/";
//        $replace[] = "[$1]";
//
//        foreach ($all_frames as $key => $value) {
//            if (substr($key, 0,1) == "T") {
//                $all_frames[$key]["tag_body"] = preg_replace($search, $replace, $value["tag_body"]);
//            }
//        }
    }
}