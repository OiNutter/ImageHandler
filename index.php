<?php
    require_once('lib/ImageHandler.php');
    
    $img = new ImageHandler("img/Crater.jpg");
    $img->Resize(array(640,460));
    $img->TextOverlay("Hello World","arial.ttf","middle",12,0,$img->new_img);
    $img->Display();
?>
