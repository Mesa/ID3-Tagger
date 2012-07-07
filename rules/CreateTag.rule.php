<?php
/**
 * @author mesa
 */

class CreateTag extends Rule
{

    public function trigger ( &$all_frames , $file)
    {
        $this->frames = &$all_frames;

        if ($this->getArtist() == null and $this->getAlbum() == null) {
            
        }
    }
}

