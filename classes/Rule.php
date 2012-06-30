<?php

abstract class Rule
{
    protected $frames;

    abstract public function trigger ( &$all_frames );

    protected function getArtist ( )
    {
        if ( isset($this->frames["TPE1"]["tag_body"])) {
            return $this->frames["TPE1"]["tag_body"];
        }
    }

    protected function setArtist ( $name )
    {
        $this->frames["TPE1"]["tag_body"] = $name;
    }

    protected function getBand ()
    {
        if (isset($this->frames["TPE2"]["tag_body"])) {
            return $this->frames["TPE2"]["tag_body"];
        }
    }

    protected function setBand ( $name )
    {
        $this->frames["TPE2"]["tag_body"] = $name;
    }

    protected function getConductor ()
    {
        if (isset($this->frames["TPE3"]["tag_body"])) {
            return $this->frames["TPE3"]["tag_body"];
        }
    }
    protected function setConductor ( $name )
    {
        $this->frames["TPE3"]["tag_body"] = $name;
    }

    protected function getOriginalArtist ()
    {
        if (isset($this->frames["TOPE"]["tag_body"])) {
            return $this->frames["TOPE"]["tag_body"];
        }
    }

    protected function setOriginalArtist ( $name )
    {
        $this->frames["TOPE"]["tag_body"] = $name;
    }

    protected function getLyrc ()
    {
        if (isset($this->frames["TEXT"]["tag_body"])) {
            return $this->frames["TEXT"]["tag_body"];
        }
    }

    protected function setLyric ( $text )
    {
        $this->frames["TEXT"]["tag_body"] = $text;
    }

    protected function getOriginalLyric ()
    {
        if (isset($this->frames["TOLY"]["tag_body"])) {
            return $this->frames["TOLY"]["tag_body"];
        }
    }

    protected function setOriginalLyric ( $text )
    {
        $this->frames["TOLY"]["tag_body"] = $text;
    }

    protected function getComposer ()
    {
        if (isset($this->frames["TCOM"]["tag_body"])) {
            return $this->frames["TCOM"]["tag_body"];
        }
    }

    protected function setComposer ( $name )
    {
        $this->frames["TCOM"]["tag_body"] = $name;
    }

    protected function getEncodedBy ()
    {
        if (isset($this->frames["TENC"]["tag_body"])) {
            return $this->frames["TENC"]["tag_body"];
        }
    }

    protected function setEncodedBy ( $text )
    {
        $this->frames["TENC"]["tag_body"] = $text;
    }

    protected function getSongLength ()
    {
        if (isset($this->frames["TLEN"]["tag_body"])) {
            return $this->frames["TLEN"]["tag_body"];
        }
    }

    protected function setSongLength ( $number )
    {
        $this->frames["TLEN"]["tag_body"] = (int) $number;
    }

    protected function getTrackNumber ()
    {
        if (isset($this->frames["TRCK"]["tag_body"])) {
            return $this->frames["TRCK"]["tag_body"];
        }
    }

    protected function setTrackNumber ( $number )
    {
        $this->frames["TRCK"]["tag_body"] = $number;
    }

    protected function getAlbum ()
    {
        if (isset($this->frames["TALB"]["tag_body"])) {
            return $this->frames["TALB"]["tag_body"];
        }
    }

    protected function setAlbum ( $album )
    {
        $this->frames["TALB"]["tag_body"] = $album;
    }

    protected function getTitle ()
    {
        if (isset($this->frames["TIT2"]["tag_body"])) {
            return $this->frames["TIT2"]["tag_body"];
        }
    }

    protected function setTitle ( $title )
    {
        $this->frames["TIT2"]["tag_body"] = $title;
    }

    protected function getTitleDescription ()
    {
        if (isset($this->frames["TIT3"]["tag_body"])) {
            return $this->frames["TIT3"]["tag_body"];
        }
    }

    protected function setTitleDescription ( $description )
    {
        $this->frames["TIT3"]["tag_body"] = $description;
    }

    protected function getPicture ()
    {
        if (isset($this->frames["APIC"]["tag_body"])) {
            return $this->frames["APIC"]["tag_body"];
        }
    }

    protected function setPicture ( $picture )
    {
        $this->frames["APIC"]["tag_body"] = $picture;
    }

    protected function getTrackYear ()
    {
        if (isset($this->frames["TYER"]["tag_body"])) {
            return $this->frames["TYER"]["tag_body"];
        }
    }

    protected function setTrackYear ( $number )
    {
        $this->frames["TYER"]["tag_body"] = $number;
    }

}