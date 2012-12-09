<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Interface for the zip extension
 * @package    phpMyAdmin
 * @version    $Id: zip_extension.lib.php 11984 2008-11-24 10:35:27Z nijel $
 */

/**
  * Gets zip file contents
  *
  * @param   string  $file
  * @return  array  ($error_message, $file_data); $error_message
  *                  is empty if no error
  * @author lem9
  */

function PMA_getZipContents($file)
{
    $error_message = '';
    $file_data = '';
    $zip_handle = zip_open($file);
    if (is_resource($zip_handle)) {
        $first_zip_entry = zip_read($zip_handle);
        if (false === $first_zip_entry) {
            $error_message = $GLOBALS['strNoFilesFoundInZip'];
        } else {
            zip_entry_open($zip_handle, $first_zip_entry, 'r');
            $file_data = zip_entry_read($first_zip_entry, zip_entry_filesize($first_zip_entry));
            zip_entry_close($first_zip_entry);
        }
    } else {
        $error_message = $GLOBALS['strErrorInZipFile'] . ' ' . PMA_getZipError($zip_handle);
    }
    zip_close($zip_handle);
    return (array('error' => $error_message, 'data' => $file_data));
}


/**
  * Gets zip error message
  *
  * @param   integer  error code
  * @return  string  error message
  * @author lem9
 */
function PMA_getZipError($code)
{
    // I don't think this needs translation
    switch ($code) {
        case ZIPARCHIVE::ER_MULTIDISK:
            $message = 'Multi-disk zip archives not supported';
             break;
        case ZIPARCHIVE::ER_READ:
            $message = 'Read error';
             break;
        case ZIPARCHIVE::ER_CRC:
            $message = 'CRC error';
             break;
        case ZIPARCHIVE::ER_NOZIP:
            $message = 'Not a zip archive';
             break;
        case ZIPARCHIVE::ER_INCONS:
            $message = 'Zip archive inconsistent';
             break;
        default:
            $message = $code;
    }
    return $message;
}
?>
