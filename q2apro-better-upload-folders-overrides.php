<?php
/*
	Plugin Name: Q2APRO Better upload folders
*/

	// don't allow this page to be requested directly from browser
	if (!defined('QA_VERSION')) 
	{
		header('Location: ../../');
		exit;
	}


	// override
	/*
		Return the full path to the on-disk directory for blob $blobid (subdirectories are named by the first 3 digits of $blobid)
	*/
	function qa_get_blob_directory($blobid)
	{
		// get created from table blobs
		$blobcreated = qa_db_read_one_value(
							qa_db_query_sub('SELECT created FROM `^blobs` 
													WHERE `blobid` = # 
											', 
											$blobid), true);
											
		// error_log('blobid: '.$blobid);
		if(is_null($blobcreated))
		{
			// fall back to default q2a core behavior
			return rtrim(QA_BLOBS_DIRECTORY, '/').'/'.substr(str_pad($blobid, 20, '0', STR_PAD_LEFT), 0, 3);
		}
		else 
		{
			// create folders Y/M/D, e.g. 2016/01/15
			$blobpath = substr($blobcreated, 0, 4).'/'.substr($blobcreated, 5, 2).'/'.substr($blobcreated, 8, 2);
			// error_log('path: '.rtrim(QA_BLOBS_DIRECTORY, '/').'/'.$blobpath);
			return rtrim(QA_BLOBS_DIRECTORY, '/').'/'.$blobpath;			
		}
	}

	// override: we only change the recursive flag for mkdir() to true so that subfolders can be created
	/*
		Write the on-disk file for blob $blobid with $content and $format. Returns true if the write succeeded, false otherwise.
	*/
	function qa_write_blob_file($blobid, $content, $format)
	{
		$written=false;

		$directory=qa_get_blob_directory($blobid);
		if (is_dir($directory) || mkdir($directory, fileperms(rtrim(QA_BLOBS_DIRECTORY, '/')) & 0777, true)) { 
			$filename=qa_get_blob_filename($blobid, $format);

			$file=fopen($filename, 'xb');
			if (is_resource($file)) {
				if (fwrite($file, $content)>=strlen($content))
					$written=true;

				fclose($file);

				if (!$written)
					unlink($filename);
			}
		}

		return $written;
	}

/*
	Omit PHP closing tag to help avoid accidental output
*/
