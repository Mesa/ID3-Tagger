<?php

abstract class Rule
{
    protected $frames;

    abstract public function trigger ( &$all_frames, $file );

    protected function renameAllTextTags( $search, $replace )
    {
        foreach ($this->frames as $key => $value) {
            if (substr($key, 0,1) == "T") {
                $this->frames[$key]["tag_body"] = preg_replace($search, $replace, $value["tag_body"]);
            }
        }
    }

    protected function getArtist ( )
    {
        if ( isset($this->frames["TPE1"]["tag_body"])) {
            return $this->frames["TPE1"]["tag_body"];
        } else {
            return false;
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
        } else {
            return false;
        }
    }

    protected function setBand ( $name )
    {
        $this->frames["TP E2"]["tag_body"] = $name;
    }

    protected function getConductor ()
    {
        if (isset($this->frames["TPE3"]["tag_body"])) {
            return $this->frames["TPE3"]["tag_body"];
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
        }
    }

    protected function setComment ( $text )
    {
        $this->frames["COMM"]["tag_body"] = $text;
    }

    protected function getComment ( $text )
    {
        if ( $this->frames["COMM"]["tag_body"] ) {
            return $this->frames["COMM"]["tag_body"];
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
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
        } else {
            return false;
        }
    }

    protected function setTrackYear ( $number )
    {
        $this->frames["TYER"]["tag_body"] = $number;
    }

}