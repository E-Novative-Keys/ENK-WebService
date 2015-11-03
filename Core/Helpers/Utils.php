<?php

class Utils
{
	public function recursiveRmdir($dir)
	{
		if(is_dir($dir))
		{
			$files = array_diff(scandir($dir), array('.', '..'));
			foreach($files as $file)
				is_dir($dir.DS.$file) ? $this->recursiveRmdir($dir.DS.$file) : unlink($dir.DS.$file);

			return rmdir($dir);
		}
		else
			return false;
	}
}

?>