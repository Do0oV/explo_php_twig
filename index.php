<?php

//initialisation de twig
require_once 'vendor/autoload.php';

$loader = new Twig_Loader_Filesystem(__DIR__.'/templates');
$twig = new Twig_Environment($loader, [
	'cache' => false, // __DIR__. '/tmp'
]);

// si le repertoire a été changé et si l'url ne contient pas de /../
if (isset($_GET['dossier']) && (strpos($_GET['dossier'], '../') === FALSE)) {
	$dossier = $_GET['dossier'];
}else{
	$dossier = 'assets';
}

// fonction pour meilleure lecture des tailles
function formatBytes($size, $precision = 2)
{
	$base = log($size, 1024);
	$suffixes = array('octets', 'Ko', 'Mo', 'Go', 'To');   
	return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

$page = 'home';

// download
if (isset($_GET['file'])) {
	$file_name = $_GET['file'];

	if(is_file($file_name)) {

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($file_name));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_name));
		ob_clean();
		flush();
		readfile($file_name);
		exit;
	}
	else {
		//die('404 File Not Found');
		//header("Refresh:0; url=index.php");
		$page = 'error';
	}
}
if (is_dir ($dossier )) {

//::UNIX_PATHS -> évite le mix entre \ et / dans les paths
	$iterator = new FilesystemIterator($dossier, FilesystemIterator::UNIX_PATHS); 

	$folders = [];
	$files = [];

// on parcourt $dossier et pour chaque élément
	foreach($iterator as $element){

	//on récupère les infos dont on a besoin pour affichage
		$dir = $element->getPathname();
		$name = $element->getFilename();
		$infos = date('d F Y - H:i:s', filemtime($element));

	//comment using Linux
	$ownerinfo = posix_getpwuid(fileowner($element));
	$owner = $ownerinfo['name'];
	$perms = fileperms($element);

	//uncomment on Wamp
		// $owner = 'Unavailable';
		// $perms = 'Unavailable';

	// si c'est un dossier
		if ($element->isDir()) {

		// compte le nombre d'éléments dans les dossiers
			$nbFiles = (count(scandir($dir)) - 2);
		// on assigne icone
			$icon = './images/folder.png';

		// on push toutes les infos dans un array $folder
			array_push($folders, [
				'name' => $name
				,'dir' => $dir
				,'nbFiles' => $nbFiles
				,'icon' => $icon
				,'infos' => $infos
				,'owner' => $owner
				,'perms' => $perms
			]);
		}

	// si c'est un fichier
		if ($element->isFile()) {

		// récupère la taille du fichier
			$size = formatBytes($element->getSize());

		// on assigne une icone en fonction de l'extension du fichier
			if(preg_match("/\.(gif|png|jpg|jpeg|svg)$/", $element)){

				$icon = './images/jpg.png';				
			}
			
			elseif (preg_match("/\.(mp3|flac|wav|wma)$/", $element)) {

				$icon = './images/mp3.png';

			}elseif (preg_match("/\.(mp4|mkv|avi|webm|ogg|mpg|mpeg|mp2|mpv|m4p|m4v|mpe|flv)$/", $element)) {

				$icon = './images/mpg.png';

			}elseif (preg_match("/\.(pdf)$/", $element)) {

				$icon = './images/pdf.png';

			}elseif (preg_match("/\.(html|php|js|xml|htm|mpg|css)$/", $element)) {

				$icon = './images/xml.png';

			}elseif (preg_match("/\.(rar|tar|zip|7z)$/", $element)) {

				$icon = './images/zip.png';

			}else{

				$icon = './images/txt.png';
			}

		//on push toutes les infos nécessaires dans un array $file
			array_push($files, [
				'name' => $name
				,'dir' => $dir
				,'size' => $size
				,'icon' => $icon
				,'infos' => $infos
				,'owner' => $owner
				,'perms' => $perms
			]);
		}
	}
}else{
	$page = 'error';
}

// en fonction de l'affectation à la variable $page on va chercher les fichiers à loader
switch ($page) {
	case 'home':
	echo $twig->render('home.twig', array(
		'dossier' => $dossier
		,'files' => $files
		,'folders' => $folders
	));
	break;
	case 'error':
	echo $twig->render('error.twig');
	break;
	default:
	echo $twig->render('home.twig');
	break;
}
?>