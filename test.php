<?php

$fh = fopen("Musik/01.mp3", "r");

echo "ID:" . fread($fh, 3) .".2.";
$data = unpack('h',fread($fh, 1));
echo $data[1] .".";
$data = unpack('h',fread($fh, 1));
echo $data[1] ."\n";
$data = unpack('c', fread($fh, 1));
echo "Flags: " . $data[1] ."\n";
$data = fread($fh, 4);
//$data = unpack('N',$data);
var_dump(decbin(syncbin2dec($data)));
var_dump(unpack('N', $data));
//echo "Size: " . $data[1] . "\n";
fclose($fh);

function syncbin2dec ( $syncbin )
{
    $true_value = "";
    $enc_syncbin = unpack('N', $syncbin);

    foreach ($enc_syncbin as $bin_value) {
        $true_value .= decbin($bin_value);
    }


    $bin_str = "";
    for ($i = 0; $i < strlen($true_value); $i++) {
        $dump = substr($true_value, -1 - $i, 1);


        if (is_int($i / 7) and $i > 0) {
            /**
                * Sollte doch eine Eins vorkommen und der Binärwert dadurch nicht Syncsafe sein
                * den unveränderten String ausgeben
                */
            if ($dump == "1") {
                $bin_str = $true_value;
                break;
            }
        } else {
            $bin_str = $dump . $bin_str;
        }
    }

    return bindec($bin_str);
}