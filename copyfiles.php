<?php

error_reporting( E_ALL );
ini_set( "display_errors", 1 );


function zipfiles($filename, $rootPath){

// Initialize archive object
$zip = new ZipArchive();
$zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Create recursive directory iterator
/** @var SplFileInfo[] $files */
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $name => $file)
{
    // Skip directories (they would be added automatically)
    if (!$file->isDir())
    {
        // Get real and relative path for current file
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive
        $zip->addFile($filePath, $relativePath);
    }
}

// Zip archive will be created only after closing object
$zip->close();
}

    function recurse_copy( $src, $dst, $is_dir ) {
        if ( $is_dir ) {
            // copy directory
            if ( is_dir( $src ) ) {
                $dir = opendir( $src );
                @mkdir( $dst );
                while ( false !== ( $file = readdir( $dir )) ) {
                    if ( ( $file != '.' ) && ( $file != '..' ) ) {
                        if ( is_dir( $src . '/' . $file ) ) {
                            recurse_copy( $src . '/' . $file, $dst . '/' . $file , true);
                        } else {
                            copy( $src . '/' . $file, $dst . '/' . $file );
                        }
                    }
                }
                closedir( $dir );
            } else {
                echo 'dir ' . $src . ' is not found!';
            }
        } else {
            // copy file
            copy( $src, $dst );
        }
    }

// make file and directory array
    function data_element( $src, $dst, $is_dir = false ) {
        $data = array();
        $data['src'] = $src;
        $data['dst'] = $dst;
        $data['isdir'] = $is_dir;
        return $data;
    }

// make data

    $data = array();

    $src = '../administrator/components/com_cgp/';
    $dst = 'Cardgate allinoneinstaller/admin/components/com_cgp/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    
    $src = '../images/stories/virtuemart/payment';
    $dst = 'Cardgate allinoneinstaller/admin/images/stories/virtuemart/payment/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    
    $src = '../plugins/vmpayment/cgpafterpay';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpafterpay/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpbanktransfer';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpbanktransfer/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpbitcoin';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpbitcoin/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpcreditcard';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpcreditcard/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpdirectdebit';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpdirectdebit/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpgeneric';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpgeneric/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpgiropay';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpgiropay/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpideal';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpideal/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpklarna';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpklarna/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpmistercash';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpmistercash/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgppaypal';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgppaypal/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpprzelewy24';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpprzelewy24/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );
    $src = '../plugins/vmpayment/cgpsofortbanking';
    $dst = 'Cardgate allinoneinstaller/admin/plugins/vmpayment/cgpsofortbanking/';
    $is_dir = true;
    array_push( $data, data_element( $src, $dst, $is_dir ) );


// copy files

    foreach ( $data as $k => $v ) {
        recurse_copy( $v['src'], $v['dst'], $v['isdir'] );
    }

// make the zip
    echo 'files copied<br>';
// Get real path for our folder
    $rootPath = '/home/richard/websites/joomlavm3/htdocs/_plugin/Cardgate allinoneinstaller';
    $filename = 'Cardgate allinoneinstaller.zip';

    zipfiles( $filename, $rootPath );
    echo 'zipfile made<br>';
    echo 'done!';
?>