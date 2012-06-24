<?php
/**
 * 
 * @author:      Mesa
 * @copyright:   
 * @version:     0.1 [BETA]
 * 
 * The goal of this class was to test my PHP skill with binary stuff. 
 * I never purposed to publish the code or use it. It was a proof of concept and
 * there is a lot of work that has to be done until this class will fit my expectations.
 * 
 *  But feel free to improve or extend the code. Feedback is welcome.
 */



define("FLAG_HEADER_UNSYNC",128);
define("FLAG_HEADER_EXTENDED",64);
define("FLAG_HEADER_EXPERIMENTAL",32);
define("FLAG_HEADER_FOOTER",16);

define("ID3_PADDING_MIN_SIZE",50);
define("ID3_PADDING_MAX_SIZE",2000);

class ID3Tag
{
	private $log = null;
	/**
	 * Contains all Information of the MP3-File
	 *
	 * @var [ARRAY]
	 */
	public $ID3_tags = array();

   /**
    * RegEx to ignore a tag. If MP3-tag will be saved, all data from the ignored tags are lost.
    * @var [REG EX]
    */
    private $ignoreTags = '/1111/i';
	/**
	 * Filehandle of the MP3 File
	 *
	 */
	private $file_handle = null;

	/**
	 * File extensions where a ID2-tag can be found.
	 *
	 * @var [ARRAY]
	 */
	private $file_types = array( "mp3", "wma");

	/**
	 * Path to MP3 File
	 *
	 * @var [STRING]
	 */
	private $path_to_file;

	/**
	 * Path and Name of the new File, which will be created, while saving tag data.
	 *
	 * @var [STRING]
	 */
	public $new_filename = null;
	
	/**
	 * Contains all error messages, for debugging purposes only.
	 *
	 * @var [ARRAY] = [STRING]
	 */
	public $errors	= array();

	/**
	 * All Sizes from File, Tag, etc...
	 *
	 * @var [ARRAY]
	 */
	public $size = array(
		"total_frame_size" 	=> 0,
		"total_tag_size" 	=> 0,
		"total_padding"		=> 0,
		"file_size" 		=> 0 );
	/**
	 * Switch logging on or off.
	 * Default is off. You need a class or a function to get some feedback.
	 *
	 * @var [BOOL]
	 *
	 * 	false = off
	 *  true  = on
	 */
	public $logging = false;
	
	/**
	 * @param [STRING] $path_to_file
	 */
	public function __construct($path_to_file)
	{
		if( $this->log == NULL and $this->logging === true )
		{
			$this->log = ScreenLog::getInstanceOf();
		}

		if( file_exists($path_to_file) )
		{

			if( true === $this->logging )
			{
				$this->log->addMsg( "[DATEI]\t $path_to_file geladen..." );
			}

			clearstatcache();
			$this->path_to_file             = $path_to_file;
			$path_info                      = pathinfo($path_to_file);
			$this->ID3_tags["extension"] 	= $path_info["extension"];
			$this->ID3_tags["filename"]		= $path_info["filename"];
			$this->ID3_tags["dirname"]		= $path_info["dirname"];
			$this->ID3_tags["path_to_file"] = $path_to_file;
			$this->size["file_size"] = filesize($this->path_to_file);

			if(in_array($this->ID3_tags["extension"],$this->file_types))
			{
				$this->file_handle = fopen($path_to_file, "rb");
				$this->readTagData();
				fclose($this->file_handle);
			} else {
				/**
				 * The expected file extension was no found in Array $this->file_types.
				 */
				$this->errors[] = "Es werden nur Dateien mit der Endung <".implode($this->file_types,", ")."> bearbeitet und nicht <".$this->ID3_tags["extension"].">";
				if(true === $this->logging )
				{
					$this->log->addMsg( "[ERROR]\t".array_pop( $this->errors ));
				}

			}
		} else {
			/**
			 * The file doesn't exist.
			 */
			$this->errors[] = "Die Datei existiert nicht <$path_to_file>";

			if(true === $this->logging )
			{
					$this->log->addMsg( "[ERROR]\t".array_pop( $this->errors ));
			}
		}
	}

        /**
         *
         * @param [STRING] $name == Name of the tag
         * @param [STRING/INTEGER] $data == Data to save.
         */
	public function addTag($name,$data)
    {
    	//@todo Überprüfung ändern da sich beide Abfragen überschneiden, bzw das gleich bewirken
    	$name = strtoupper( substr( $name, 0 , 4 ) );

		$data = mb_convert_encoding($data,'ISO-8859-1','auto');

    	if( preg_match('/^[A-Z][A-Z0-9]{3}$/', $name))
    	{
    		if( true === $this->logging )
    		{
    			$this->log->addMsg( "[$name] - {$data} hinzugefügt" );
    		}
    		
            $this->ID3_tags[$name]["data"] = $data;
    	}
    }

        /**
         * Save all frames to file and overwrite the old data.
         */
	public function saveTags()
	{

		$tag_data 		= "";

		foreach ($this->ID3_tags as $key=>$data)
		{
	        if( preg_match('/^[A-Z][A-Z0-9]{3}$/', $key))
            {
           	 /**
              * Tags auslassen die in dem Regulären Ausdruck <ignoreTags> sind
              */
	            if(!preg_match($this->ignoreTags, $key) )
	            {
					$tag_data .= $this->createFrame($key,$data["data"],0);
	            }
         	}
		}
		/**
		 * ID3V2.4 create Header
		 *  STRING: ID3
		 *  HEX:	4
		 *  HEX:	0
		 *  Flag:	0
		 *
		 */
		$header_data 		= "ID3".pack('h',0x04).pack('h',0x00).pack('C',decbin(0));
		$new_data_length 	= strlen( $tag_data ) + strlen( $header_data );

		/**
		 * Check for given Header size in file. 
		 */
		if( $new_data_length > $this->size["total_tag_size"] or $this->size["total_padding"] < ID3_PADDING_MIN_SIZE or $this->size["total_padding"] > ID3_PADDING_MAX_SIZE )
		{
			/**
			 * Not enough padding or to large padding.
			 * create new file with ID3_PADDING_MAX_SIZE / 2
			 */

			$this->overwriteFile($header_data,$tag_data);
		} else {
			/**
			 * Enough padding.
			 * Overwrite old header
			 */
			$this->extendFile($header_data, $tag_data);
		}
	}

	private function extendFile($header_data, $tag_data)
	{
		
		$file_handle 	= fopen( $this->path_to_file , 'r+b');
		
		if( true === $this->logging )
		{
			$this->log->addMsg( "[SPECIHERN] Alte Daten werden überschrieben..." );
		}

		for ($i = 0; $i < $this->size["total_padding"]; $i++)
		{
			$tag_data .= pack('h',0);
		}

		$header_data .= $this->dec2syncbin(strlen($tag_data));
		/**
		 * Set pointer to beginning of file
		 */
		fseek($file_handle, 0, SEEK_SET);
		fwrite($file_handle,$header_data.$tag_data);
	}

	private function overwriteFile($header_data,$tag_data,$path = null)
	{
		/**
		 * @todo Prüfen ob ein neuer Pfad gespeichert wurde und dann ggf. die Datei
		 * unter neuem Namen speichern und die Quelldatei löschen
		 */
		$file_handle 	= fopen( $this->path_to_file , 'r+b');

		if($this->logging === true)
		{
			$this->log->addMsg( "[SPECIHERN] Datei wird neu erstellt..." );
		}


		fseek( $file_handle,$this->size["total_tag_size"], SEEK_SET );

		$audio_data = fread( $file_handle,$this->size["file_size"] );

		fclose( $file_handle );

		for( $i=0; $i <= round(ID3_PADDING_MAX_SIZE / 2) ; $i++ )
		{
			$tag_data .= pack('h',0x00);
		}

			$header_data .= $this->dec2syncbin(strlen($tag_data));

			if($path == null)
			{
				$file_handle = fopen($this->path_to_file,'wb');
				fwrite($file_handle,$header_data.$tag_data.$audio_data);

			} else {
				$new_file = fopen($path,'wb');
				fwrite($new_file,$header_data.$tag_data.$audio_data);
				fclose($new_file);
			}
	}

        /**
         * Einen Frame erstellen und die vorhandenden Daten ensprechend codieren
         *
         * @param [STRING] $name == Name des Frame
         * @param [STRING/INTEGER] $data == Daten die innerhalb des Frame gespeichert werden sollen
         * @param [UNUSED]
         *
         * @todo Um die Behandlung der Flags erweitern
         */
	private function createFrame($name,$data,$flags)
	{
		if( file_exists( $path ) )
		{
			include "tag_functions/".htmlentities($name).".write.php";
		} else {
			include 'tag_functions/default.write.php';
		}

		return $frame_content;
	}

        /**
         * Binären String in Synchsafe integer umwandeln
         * (Besonderheit ist das die 8. Stelle IMMER "0" ist)
         *
         * @param [INTEGER] $bin Dezimalwert der in einen Syncsafe Binärwert umgewandelt werden soll (vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge Big Endian) )
         * @param [INTEGER] $bits Anzahl der Bits die der Binärwert haben soll
         * @todo den Parameter $bits entfernen, da dieser durch die Verwendung von 'N' bei pack() überflüssig geworden ist
         */
        private function dec2syncbin($bin)
        {
            $bin_data = decbin($bin);
            $syncsafe_bin = null;

            for($i=0;$i < strlen($bin_data) ;$i++)
            {
                $dump = (int) substr($bin_data,-1-$i,1);

                if( is_int( strlen($syncsafe_bin)/7) and $i > 0)
                {
                    $syncsafe_bin = $dump."0".$syncsafe_bin;
                } else {
                    $syncsafe_bin = $dump.$syncsafe_bin;
                }
            }

            /* String in Binär Daten umwandeln */
            return pack('N', base_convert($syncsafe_bin, 2, 10));
        }

        /**
         * Entfernt jede 8. Stelle aus dem übergeben 4 Byte langen Binärcode
         *
         * @param [BINÄR INTEGER] $syncbin (vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge Big Endian)
         * @return [INTEGER]
         */
        private function syncbin2dec($syncbin)
        {
            $true_value = "";
            $enc_syncbin = unpack('N',$syncbin);

            foreach ($enc_syncbin as $bin_value)
            {
                $true_value .= decbin($bin_value);
            }


            $bin_str = "";
            for( $i=0; $i < strlen($true_value) ;$i++ )
            {
                $dump = substr( $true_value , -1-$i ,1 );


                if( is_int($i/7) and $i > 0 )
                {
                    /**
                     * Sollte doch eine Eins vorkommen und der Binärwert dadurch nicht Syncsafe sein
                     * den unveränderten String ausgeben
                     */
                    if($dump == "1")
                    {
                        $bin_str = $true_value;
                        break;
                    }
                } else {
                    $bin_str = $dump.$bin_str;
                }
            }

            return bindec($bin_str);
        }

        /**
         * Liest alle Tag Daten der Datei ein und wandelt die Datentypen um
         *
         */
	private function readTagData()
	{
		$this->ID3_tags["tag_version"]	= fread( $this->file_handle, 3 );
		$sub_version_1 = unpack('h' , fread( $this->file_handle, 1 ) );
		$sub_version_2 = unpack('h' , fread( $this->file_handle, 1 ) );

		if($sub_version_2[1] == 0)
		{
			$this->ID3_tags["tag_version"]	.= "v2.". $sub_version_1[1];
		} else {
			$this->ID3_tags["tag_version"]	.= "v2.".$sub_version_1[1] .".". $sub_version_2[1];
		}

		$header_flags = fread( $this->file_handle, 1 ) ;

		/**
		 * %abcd0000
		 */

		if( FLAG_HEADER_UNSYNC & $header_flags == FLAG_HEADER_UNSYNC )
		{
			$this->ID3_tags["header_flags"]["unsync"] = true;
		} else {
			$this->ID3_tags["header_flags"]["unsync"] = false;
		}

		if( FLAG_HEADER_EXTENDED & $header_flags == FLAG_HEADER_EXTENDED )
		{
			$this->ID3_tags["header_flags"]["extended"] = true;
		} else {
			$this->ID3_tags["header_flags"]["extended"] = false;
		}

		if( FLAG_HEADER_EXPERIMENTAL & $header_flags == FLAG_HEADER_EXPERIMENTAL )
		{
			$this->ID3_tags["header_flags"]["experimental"] = true;
		} else {
			$this->ID3_tags["header_flags"]["experimental"] = false;
		}

		if( FLAG_HEADER_FOOTER & $header_flags == FLAG_HEADER_FOOTER )
		{
			$this->ID3_tags["header_flags"]["footer"] = true;
		} else {
			$this->ID3_tags["header_flags"]["footer"] = false;
		}

		/**
		 * Die Größe des Tags auslesen incl. ggf padding + 10 Byte Header
		 */
		$this->size["total_tag_size"] = $this->syncbin2dec( fread($this->file_handle, 4 ) ) + 10;
        $tag_name = array();
		$this->size["total_frame_size"] = 0;

		while(preg_match('/^[A-Z][A-Z0-9]{3}$/', fread( $this->file_handle, 4 ),$tag_name ) )
		{
			unset($frame_size);

			/**
			 * $frame_size  enthält nur dir Göße der Daten innerhalb des Frames
			 */
			$frame_size = $this->syncbin2dec( fread( $this->file_handle, 4) ) ;

			/**
			 *  Framesize + 10 Byte Frameheader
			 *  ==
			 *  + 4 Frame Name
			 *  + 4 Frame Size
			 *  + 2 Frame Flags
			 */
            $this->size["total_frame_size"] += $frame_size + 10;

			$this->ID3_tags[$tag_name[0]]["frame_size"] = $frame_size;
			/**
			 * %0abc0000 %0h00kmnp
			 *  Frame status flags auslesen
			 */
			if(preg_match($this->ignoreTags,$tag_name[0]))
            {
                fseek($this->file_handle,ftell($this->file_handle)+5);
                fseek($this->file_handle,ftell($this->file_handle)+$frame_size);
                continue;
            }

			$frame_1_flag = fread( $this->file_handle, 1 );
			$frame_2_flag = fread( $this->file_handle, 1 );

			/**
			 * @todo Tagbehandlung hinzufügen
			 *
			 * Aktuelle Behandlung ist Fehlerhaft
			 */
			/*
			 * %0[a]bc0000 %0h00kmnp
			 * Tag alter preservation
			 */
			$f_flag["tag_alter"] = pack('C', 64);

			if( $f_flag["tag_alter"] & $frame_1_flag == $f_flag["tag_alter"])
			{
				$this->ID3_tags[$tag_name[0]]["tag_alter"] = true;
			} else {
				$this->ID3_tags[$tag_name[0]]["tag_alter"] = false;
			}
			/*
			 * %0a[b]c0000 %0h00kmnp
			 * File alter preservation
			 */
			$f_flag["file_alter"] = pack('C', 32);
			if( $f_flag["file_alter"] & $frame_1_flag == $f_flag["file_alter"] )
			{
				$this->ID3_tags[$tag_name[0]]["file_alter"] = true;
			} else {
				$this->ID3_tags[$tag_name[0]]["file_alter"] = false;
			}
			/*
			 * %0ab[c]0000 %0h00kmnp
			 * File alter preservation
			 */
			$f_flag["read_only"] = pack('C', 16);
			if( $f_flag["read_only"] & $frame_1_flag == $f_flag["read_only"] )
			{
				$this->ID3_tags[$tag_name[0]]["read_only"] = true;
			} else {
				$this->ID3_tags[$tag_name[0]]["read_only"] = false;
			}

 	          $this->readFrame( $tag_name[0] );
		}

		$this->size["total_padding"] = 0;

		while( pack('H',0x00) == fread($this->file_handle,1) )
		{
			$this->size["total_padding"]++;
		}

		/**
		 * wenn die gesammtgröße - gesammt größe der frames - Header (10byte) !== total padding ist
		 * ist die angegebene größe falsch
		 *
		 */
		if($this->size["total_padding"] !== ($this->size["total_tag_size"] - $this->size["total_frame_size"] - 10 ) )
		{
			if($this->logging === true)
			{
				$this->log->addMsg( "[INFO]\t Falsche Größenangaben, berechne diese jetzt neu" );
				$this->log->addMsg( "-\t Größe vorher:".$this->size["total_tag_size"]);
				$this->size["total_tag_size"] = $this->size["total_frame_size"] + $this->size["total_padding"] + 10;
				$this->log->addMsg( "-\t Größe nachher:".$this->size["total_tag_size"]);
			}
		}

		/**
		 * @todo Genauere Fehlerbehandlung hinzufügen
		 */
		if($this->size["total_padding"] < 0)
		{
			if($this->logging === true)
			{
				$this->log->addMsg( "[INFO]\tFehlerhaft Formatierte Datei...\n [".$this->path_to_file."]\n\n" );
			}
			$this->size["total_padding"]=0;
		}
	}

		/**
		 * Lädt die passende Datei um den Frameinhalt richtig zu interpretieren
		 *
		 * @param [STRING] $name (Tag name)
		 */
		private function readFrame( $name )
		{
			if( file_exists( $path ) )
			{
				include "tag_functions/".htmlentities($name).".tag.php";
			} else {
				include 'tag_functions/default.tag.php';
			}
		}

        /**
         * Convertiert die Codierung eines Strings, ausgehend vom Hex Wert der übergeben wird
         *
         * @param [BINARY DATA] $data
         * @param [HEXA] $encodingHex
         *
         * @return [STRING]
         */
        private function convertEncoding($frame_data, $encodingHex)
        {
            $string = array();

            if( "01" == $encodingHex or "02" == $encodingHex )
            {
				$string["encoding"] = "UTF-16";
				$string["data"]     = mb_convert_encoding($frame_data ,"auto","UTF-16");

            } elseif ( "03" == $encodingHex )
            {
			/**
        	 * Frame ist utf-8 codiert
		 	 */
                $string["encoding"] = "UTF-8";
                $string["data"]     = trim(substr($frame_data,1,strlen($frame_data)));

            } elseif ( "00" == $encodingHex )
            {
			/**
        	 * Frame ist ISO-8859-1 codiert
			 */
                $string["encoding"] = "ISO-8859-1";
                $string["data"]     = trim($frame_data);
            }
            return $string;
        }
}
?>
