<?php
error_reporting(0);
header('Access-Control-Allow-Origin: *');
if (!file_exists('.htaccess')) {
    $content = 'RewriteEngine On' . "\n";
    $content .= 'Options -Indexes' . "\n";
    $content .= 'RewriteRule ^([^/]*)/([^/]*)/?$ ?id=$1&name=$2 [L]' . "\n";
    $content .= 'RewriteRule ^([^/]*)/([^/]*)/([^/]*)x([^/]*)/?$ ?id=$1&name=$2&w=$3&h=$4 [L]' . "\n\n";
    $content .= 'ErrorDocument 403 /' . "\n";
    $content .= 'ErrorDocument 404 /' . "\n";
    $content .= 'ErrorDocument 500 /';

    $f = fopen(".htaccess", "a+");
    fwrite($f, $content);
    fclose($f);
}
$file_info = new finfo();
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$actual_link = str_replace("/index.php", "", $actual_link);
if (isset($_FILES['file']['name'])) {
    // file name
    $filename = $_FILES['file']['name'];

    $id = sha1(md5(sha1(time() . rand(1, 34273645) . rand(1, 576293263))));

    if (!file_exists('upload')) {
        mkdir('upload');
    }

    // Location
    $location = 'upload/' . $id . '/' . $filename;

    // file extension
    $file_extension = pathinfo($location, PATHINFO_EXTENSION);
    $file_extension = strtolower($file_extension);

    // Valid image extensions
    $valid_ext = array("zip", "pdf", "doc", "docx", "jpg", "png", "jpeg");
    $response = [];
    if (TRUE) {
        // Upload file
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(file_get_contents($_FILES['file']['tmp_name']));
        if (strpos($mime_type, 'image') !== false) {
            ImageResize(0, 0);
        }
        $tmp = $_FILES['file']['tmp_name'];
        mkdir('upload/' . $id);
        if (move_uploaded_file($tmp, $location)) {
            $response['status'] = 1;
            $response['path'] = $filename;
            $response['file_name'] = str_replace("." . $file_extension, "", $filename);
            $response['file_extension'] = $file_extension;
            $response['file_size'] = filesize($location) . " bytes";
            $response['file_size_number'] = filesize($location);
            $response['file_size_unit'] = 'bytes';
            $response['url'] = $actual_link . '/' . $id . '/' . $filename;
        } else {
            $response['status'] = 0;
            $response['path'] = '';
            $response['file_name'] = '';
            $response['file_extension'] = '';
            $response['file_size'] = '';
            $response['file_size_number'] = '';
            $response['file_size_unit'] = '';
        }
    } else {
        $response['status'] = 0;
        $response['path'] = '';
        $response['file_name'] = '';
        $response['file_extension'] = '';
        $response['file_size'] = '';
        $response['file_size_number'] = '';
        $response['file_size_unit'] = '';
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['name']) && !empty($_GET['name'])) {
    if (file_exists('upload/' . $_GET['id'] . '/' . $_GET['name'])) {
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(file_get_contents('upload/' . $_GET['id'] . '/' . $_GET['name']));
        header('Content-Type: ' . $mime_type);

        $w = isset($_GET['w']) && !empty($_GET['w'])?$_GET['w']:'';
        $h = isset($_GET['h']) && !empty($_GET['h'])?$_GET['h']:'';
        $q = isset($_GET['q']) && !empty($_GET['q'])?$_GET['q']:'';

        if (!empty($w) && empty($h)) {
            $w_ = $w;
            list($width, $height) = getimagesize('upload/' . $_GET['id'] . '/' . $_GET['name']);
            $w = ceil($width*($w_/100));
            $h = ceil($height*($w_/100));
        }

        if (!empty($w) && !empty($h) && strpos($mime_type, 'image') !== false) {
            readfile(ImageResize($w, $h, 'upload/' . $_GET['id'] . '/' . $_GET['name'], true), isset($_GET['q']) && !empty($_GET['q'])?$_GET['q']:'');
            exit;
        } else {
            readfile('upload/' . $_GET['id'] . '/' . $_GET['name']);
            exit;
        }
    }
}

function resize_image($file, $w, $h, $type = '', $crop = false)
{
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width - ($width * abs($r - $w / $h)));
        } else {
            $height = ceil($height - ($height * abs($r - $w / $h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w / $h > $r) {
            $newwidth = $h * $r;
            $newheight = $h;
        } else {
            $newheight = $w / $r;
            $newwidth = $w;
        }
    }
    if ($type === 'image/png') {
        $src = imagecreatefrompng($file);
    } else if ($type === 'image/jpg' || $type === 'image/peg') {
        $src = imagecreatefromjpeg($file);
    }if ($type === 'image/gif') {
        $src = imagecreatefromgif($file);
    }
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    return $dst;
}

function ImageResize($width, $height, $img_name = '', $return = false, $q = '')
{
    /* Get original file size */

    if (!empty($img_name)) {
        $_FILES['file']['tmp_name'] = $img_name;
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(file_get_contents($_FILES['file']['tmp_name']));
        $_FILES['file']['type'] = $mime_type;
    }
    list($w, $h) = getimagesize($_FILES['file']['tmp_name']);

    if ($width == 0) {
        $width = $w;
    }
    if ($height == 0) {
        $height = $h;
    }

    /* Calculate new image size */
    $ratio = max($width / $w, $height / $h);
    $h = ceil($height / $ratio);
    $x = ($w - $width / $ratio) / 2;
    $w = ceil($width / $ratio);
    /* set new file name */
    $path = $img_name;

    /* Save image */
    if ($_FILES['file']['type'] == 'image/jpeg') {
        /* Get binary data from image */
        $imgString = file_get_contents($_FILES['file']['tmp_name']);
        /* create image from string */
        $image = imagecreatefromstring($imgString);
        $tmp = imagecreatetruecolor($width, $height);
        imagecopyresampled($tmp, $image, 0, 0, $x, 0, $width, $height, $w, $h);
        if ($return) {
            return imagejpeg($tmp, NULL, isset($q) && !empty($q)?$q:60);
        } else {
            imagejpeg($tmp, $_FILES['file']['tmp_name'], 60); // from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default (-1) uses the default IJG quality value (about 75).
        }
    } else if ($_FILES['file']['type'] == 'image/png') {
        $image = imagecreatefrompng($_FILES['file']['tmp_name']);
        $tmp = imagecreatetruecolor($width, $height);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagecopyresampled($tmp, $image, 0, 0, $x, 0, $width, $height, $w, $h);
        if ($return) {
            return imagepng($tmp, NULL, isset($q) && !empty($q)?$q:9);
        } else {
            imagepng($tmp, $_FILES['file']['tmp_name'], 9); // Compression level: from 0 (no compression) to 9. The default (-1) uses the zlib compression default.
        }
    } else if ($_FILES['file']['type'] == 'image/gif') {
        $image = imagecreatefromgif($_FILES['file']['tmp_name']);

        $tmp = imagecreatetruecolor($width, $height);
        $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefill($tmp, 0, 0, $transparent);
        imagealphablending($tmp, true);

        imagecopyresampled($tmp, $image, 0, 0, 0, 0, $width, $height, $w, $h);
        if ($return) {
            return imagegif($tmp, NULL);
        } else {
            imagegif($tmp, $_FILES['file']['tmp_name']);
        }
    } else {
        return false;
    }

    if (!$return) {
        return true;
    }

    imagedestroy($image);
    imagedestroy($tmp);
}

