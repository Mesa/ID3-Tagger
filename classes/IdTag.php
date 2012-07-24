<?php

/**
 * @author Mesa <mesa@xebro.de>
 */
class IdTag
{

    /**
     * Contains all existing frames from the ID Tag
     * @var [Array] ID tags
     */
    protected $frames           = array();
    protected $tag_version      = null;
    protected $tmp_file_name    = "tmp_";
    protected $tag_size         = null;

    /**
     * File extensions where a ID2-tag can be found.
     *
     * @var [Array]
     */
    protected $allowed_file_types = array("mp3", "wma");

    /**
     * Filehandle of the MP3 File
     *
     */
    protected $file_handle      = null;
    protected $file_extension   = null;
    protected $file_path        = null;
    protected $file_size        = null;

    /**
     * change IdTag encoding to utf8?
     *
     * @var [Boolean]
     */
    protected $change_encoding = true;

    /**
     * Size of padding behind tags
     *
     * @var [Integer]
     */
    protected $tag_padding      = 0;

    /**
     * When file has not enough padding, copy file an add this amount of zeros
     * behind all frames
     *
     * @var [Integer]
     */
    protected $new_padding      = 1000;

    const UTF8ENCODING          = 3;
    const ISOENCODING           = 0;
    const UTF16ENCODING1        = 1;
    const UTF16ENCODING2        = 2;

    protected $header_flag_usync = false;
    protected $header_flag_ext   = false;
    protected $header_flag_exp   = false;
    protected $header_flag_footer= false;

    const HEADER_FLAG_USYNC     = 128;
    const HEADER_FLAG_EXT       = 64;
    const HEADER_FLAG_EXP       = 32;
    const HEADER_FLAG_FOOTER    = 16;

    protected $remove_v1        = false;
    protected $has_tag_v1       = false;
    protected $has_tag_v2       = false;

    public function getAllFrames ()
    {
        return $this->frames;
    }

    public function setV1TagHandling ( $action )
    {
        if ( strtolower($action) == "remove") {
            $this->remove_v1 = true;
        } else {
            $this->remove_v1 = false;
        }

    }

    protected function createIdTagV24Header ()
    {
        $header = "ID3";
        $header .= pack('hhC', 0x04, 0x00, decbin(0));
        return $header;
    }

    protected function createTag( $array )
    {
        $all_frames = "";
        foreach ($array as $frame) {
            $tmp = $this->writeFrame($frame);
            if ($tmp !== false) {
                $all_frames .= $tmp;
            }
            $tmp = "";
        }

        $new_tag_length = strlen($all_frames) + 10;
        if ( $new_tag_length == 10) {
            /**
             * exit if no frames was added
             */
            return "";
        }

        if ($new_tag_length < $this->tag_size) {
            $new_tag_size = $this->tag_size;
            $spacing = $this->tag_size - $new_tag_length;
        } else {
            $new_tag_size = $new_tag_length + $this->new_padding;
            $spacing = $this->new_padding;
        }

        $new_tag =
            $this->createIdTagV24Header()
            . $this->dec2syncbin($new_tag_size)
            . $all_frames
            . $this->createPadding($spacing);

        return $new_tag;
    }

    protected function writeIntoFile( $idTag )
    {
        rewind($this->file_handle);
        fwrite($this->file_handle, $idTag, strlen($idTag));
    }

    protected function writeNewFile( $idTag )
    {
        if ( $this->file_size <= 0 ) {
            $this->file_size = filesize($this->file_path);
        }

        fseek($this->file_handle, $this->tag_size);

        $music = fread($this->file_handle, $this->file_size);
        rewind($this->file_handle);
        fwrite($this->file_handle, $idTag . $music);
    }

    public function saveTag ( $array )
    {
        echo " ";
        if ($array != $this->frames or (!$this->has_tag_v2 and $this->has_tag_v1) ) {
           /**
            * only write changes to file, if Array has changed, or data is from
            * Tag V1 and no V2 Tag exists.
            */
           $idTag = $this->createTag( $array );
           $tag_size = strlen($idTag) - 1;

           if ( $tag_size == $this->tag_size and $tag_size > 11) {
               $this->writeIntoFile($idTag);
               echo "W";
           } else {
               $this->writeNewFile($idTag);
               echo "c";
           }
        } else {
            echo ".";
        }

        if ( $this->has_tag_v1 and $this->remove_v1) {
            $this->removeV1Tag();
            echo "R";
        }
    }

    protected function removeV1Tag()
    {
        ftruncate($this->file_handle, filesize($this->file_path) - 128);
    }

    protected function writeFrame ( $array )
    {
        if (isset($array["tag_name"])
            and isset($array["tag_body"])
            and strlen($array["tag_name"]) == 4
            and strlen($array["tag_body"]) > 0
        ) {
            $frame_body = "";

            if (substr(trim($array["tag_name"]), 0, 1) == "T") {
                if ( !isset($array["tag_enc"]) ) {

                    $array["tag_body"] = $this->convertEncoding(
                        $array["tag_body"],
                        IdTag::UTF8ENCODING
                    );
                    $array["tag_enc"] = IdTag::UTF8ENCODING;
                }
                $frame_body .= pack('C', $array["tag_enc"]);
            }

            $frame_body .= $array["tag_body"];

            $frame_length = strlen($frame_body);

            $frame_header = strtoupper($array["tag_name"]);
            $frame_header .= $this->dec2syncbin($frame_length);

            if (!isset($array["tag_flag_1"])) {
                $array["tag_flag_1"] = 0;
            }

            if (!isset($array["tag_flag_2"])) {
                $array["tag_flag_2"] = 0;
            }

            $frame_header
                .= pack(
                    'HH',
                    $array["tag_flag_1"],
                    $array["tag_flag_2"]
                );

            $data = $frame_header . $frame_body;
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Reset all data for new File.
     *
     * @return void
     */
    protected function resetData ()
    {
        $this->header_flag_exp      = false;
        $this->header_flag_ext      = false;
        $this->header_flag_footer   = false;
        $this->header_flag_usync    = false;
        $this->tag_padding          = 0;
        $this->tag_size             = 0;
        $this->tag_version          = null;
        $this->frames               = array();
        $this->file_path            = null;
        $this->has_tag_v1           = false;
        $this->has_tag_v2           = false;

        if ($this->file_handle != null) {
            fclose($this->file_handle);
            $this->file_handle = null;
        }
    }

    /**
     * Load complete Tag from <$file_path>
     *
     * @param [String] $file_path Path to mp3 file.
     *
     * @throws IdTagException
     */
    public function loadTags ( $file_path )
    {
        if (file_exists($file_path)) {
            if (is_readable($file_path) and is_writable($file_path)) {
                $this->resetData();
                $this->file_path = $file_path;
                $this->file_handle = fopen($file_path, "r+");
                /**
                 * load Tag V1 first and if V2.4 exist overwrite loaded information.
                 */
                $this->loadTagV1();
                $this->loadHeader();
                if ($this->has_tag_v2) {
                    while ($this->readFrame()) {
                    }
                    $this->getPadding();
                } else {
                    /**
                     * no Tag found.
                     */
                }
            } else {
                throw new IdTagException("File is not writeable " . $file_path);
            }
        } else {
            throw new IdTagException("File not found " . $file_path);
        }
    }

    /**
     * get Information for ID Tag V1
     */
    protected function loadTagV1 ()
    {
        fseek($this->file_handle, -128, SEEK_END);
        if ("TAG" == fread($this->file_handle, 3) ) {
            $this->has_tag_v1 = true;

            $title = fread($this->file_handle, 30);
            $this->frames["TIT2"]["tag_body"] = $title;
            $this->frames["TIT2"]["tag_name"] = "TIT2";

            $artist = fread($this->file_handle, 30);
            $this->frames["TPE1"]["tag_body"] = $artist;
            $this->frames["TPE1"]["tag_name"] = "TPE1";

            $album = fread($this->file_handle, 30);
            $this->frames["TALB"]["tag_body"] = $album;
            $this->frames["TALB"]["tag_name"] = "TALB";

            $year = fread($this->file_handle, 4);
            $this->frames["TYER"]["tag_body"] = $year;
            $this->frames["TYER"]["tag_name"] = "TYER";

            $comment = fread($this->file_handle, 30);
            $this->frames["COMM"]["tag_body"] = $comment;
            $this->frames["COMM"]["tag_name"] = "COMM";
        } else {
            $this->has_tag_v1 = false;
        }
        rewind($this->file_handle);
    }
    /**
     * Create a hex string with <$size> length
     *
     * @param [Integer] $size How long should be the string
     *
     * @return [Integer] Hex string
     */
    protected function createPadding ( $size )
    {
        $data = "";

        for ($i=0; $i<=$size; $i++) {
            $data .= pack("H", 0x00);
        }
        return $data;
    }
    /**
     * Count padding after Tag. Padding is always a hex zero.
     */
    protected function getPadding ()
    {
        while ($this->readFromFile(1, "hex") == 0) {
            $this->tag_padding++;
        }
    }

    /**
     * Read the first 10 bytes from file
     */
    protected function loadHeader ()
    {
        $version = $this->readFromFile(3, "string");

        if ($version == "ID3") {
            $version2 = (int) $this->readFromFile(1, "hex");
            if ($version2 > 0) {

            $this->tag_version
                = "ID3.2" . $version2 . $this->readFromFile(1, "hex");
            }
            $flags = $this->readFromFile(1);

            if ($flags & IdTag::HEADER_FLAG_USYNC == IdTag::HEADER_FLAG_USYNC) {
                $this->header_flag_usync = true;
            }

            if ($flags & IdTag::HEADER_FLAG_EXT == IdTag::HEADER_FLAG_EXT) {
                $this->header_flag_ext = true;
                $this->readExtHeader();
            }

            if ($flags & IdTag::HEADER_FLAG_EXP == IdTag::HEADER_FLAG_EXP) {
                $this->header_flag_exp = true;
            }

            if ($flags & IdTag::HEADER_FLAG_FOOTER == IdTag::HEADER_FLAG_FOOTER) {
                $this->header_flag_footer = true;
            }

            $this->tag_size = $this->readFromFile(4, "sync");
            $this->has_tag_v2 = true;
        } else {
            $this->has_tag_v2 = false;
            $this->tag_version = "";
            $this->tag_size = 0;
        }
    }

    protected function readFrame ()
    {
        $data = array();

        $tag_name = $this->readFromFile(4);
//        $tag_name = $data["tag_name"];
        if (preg_match('/^[A-Z][A-Z0-9]{3}$/', $tag_name)) {
            $data["tag_name"] = $tag_name;

            $data["tag_size"] = $this->readFromFile(4, "sync");
            $data["tag_flag_1"] = $this->readFromFile(1, "hex");
            $data["tag_flag_2"] = $this->readFromFile(1, "hex");

            if (substr($tag_name, 0, 1) == "T") {
                $tmp = $this->readTextFrame($this->readFromFile($data["tag_size"]));
                $data = array_merge($data, $tmp);
            } else {
                $data["tag_body"] = $this->readFromFile($data["tag_size"]);
            }
            /**
             * Frames that allow different types of text encoding contains a
             * text encoding description byte.
             */
            $this->frames[$tag_name] = $data;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create Array from Tag frame.
     *
     * @return [Array]
     */
    protected function readTextFrame ( $data )
    {
        $tag["tag_enc"] = $this->hex2int(substr($data, 0, 1));
        $tag_body = substr($data, 1, strlen($data));

        switch ($tag["tag_enc"]) {
        case IdTag::ISOENCODING:
            /**
                * Frame is ISO-8859-1 encoded
                */
            if ($this->change_encoding) {
                $tag["tag_body"]
                    = $this->convertEncoding(
                        substr($tag_body, 0, strlen($tag_body) - 1), IdTag::UTF8ENCODING, "ISO-8859-1"
                    );
                $tag["tag_enc"] = IdTag::UTF8ENCODING;
            } else {
                $tag["tag_body"] = $tag_body;
            }
            break;

        case IdTag::UTF8ENCODING:
            /**
                * Frame is UTF-8 encoded
                */
            $tag["tag_body"] = $tag_body;
            break;

        case IdTag::UTF16ENCODING1:
        case IdTag::UTF16ENCODING2:
            /**
                * Frame is UTF-16 encoded
                */
            $tag["tag_body"] = $tag_body;
            break;
        }
        return $tag;
    }

    protected function convertEncoding ( $frame_data, $to_encoding, $from_encoding = "auto" )
    {
        $string = array();

        switch ($to_encoding) {
        case IdTag::ISOENCODING:
            $target_encoding = "ISO-8859-1";
            break;

        case IdTag::UTF16ENCODING1:
        case IdTag::UTF16ENCODING1:
            $target_encoding = "UTF-16";
            break;

        case IdTag::UTF8ENCODING:
        default:
            $target_encoding = "UTF-8";
        }

        return mb_convert_encoding($frame_data, $target_encoding, $from_encoding);
    }

    protected function readExtHeader ()
    {
        /**
         * @todo implement extendet header handling
         */
    }

    /**
     * Read expected value from file and return data converted to integer or
     * data not manipulated.
     *
     * @param [Integer] $length Bit count
     *
     * @param [String] $type expected value is [Hex|Bin|SyncBin]
     *
     * @return [Integer]
     */
    protected function readFromFile ( $length, $type = null )
    {
        if ( $length > 0 ) {
            $data = fread($this->file_handle, (int) $length);
            switch ($type) {
            case "bin":
                return $this->bin2int($data);
                break;
            case "hex":
                return $this->hex2int($data);
                break;

            case "sync":
                return $this->syncbin2dec($data);
                break;
            default :
                return $data;
            }
        }
    }

    /**
     * Convert sync safe binary (4 bytes) to decimal integer.
     *
     * @param [Integer] $syncbin (vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge Big Endian)
     *
     * @return [Integer]
     */
    protected function syncbin2dec ( $sync )
    {
        $sync_length = strlen($sync);
        $bin = 0;

        for ( $i = 0; $i < $sync_length; $i++ ) {
            $data = unpack('c', substr($sync, $i, 1));
            if ( $data[1] >= 128 ) {
                echo "Syncsafe byte could not be greater than 128 in Tagsize.";
            } else {
                $bin += $data[1] << 7 * ( $sync_length - $i - 1 );
            }
        };
        return $bin;
    }

    /**
     * Convert decimal integer to 4 byte long sync safe binary.
     * (Besonderheit ist das die 8. Stelle IMMER "0" ist)
     *
     * @param [Integer] $bin decimal integer to convert (vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge Big Endian) )
     *
     * @return 32 Bit Binary
     */
    protected function dec2syncbin ( $dec )
    {
        $byte_length = strlen(decbin($dec));
       /**
        * create Bit mask. Set all 7 bits to 1
        */
        $mask = 127;
        $bin = 0;

        /** bit shift with 7 characters. */
        for ($i=0; $i < $byte_length / 7; $i++) {
            $tmp = $dec;
            $tmp2 = $tmp & ($mask << 7 * $i);
            $bin += $tmp2 << 1 * $i;
        }
        return pack('N', $bin);
    }

    protected function bin2Int ( $bin )
    {
        return (int) bindec($bin);
    }

    protected function hex2int ( $hex )
    {
        if ($hex != null) {
            $data = unpack('h', $hex);
            return (int) $data[1];
        } else {
            return null;
        }
    }
}

