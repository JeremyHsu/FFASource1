<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Main extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index() {
		$data = array();
		if (isset($_COOKIE['uid']))
			$data['uid'] = $_COOKIE['uid'];
		$this -> load -> model('news');
		$data["action"] = "/main/login";
		$data["news"] = $this -> news -> get_entries();
		$data["top"] = json_encode($this -> getTopMemberList());
		$this -> load -> view('header', $data);
		$this -> load -> view('main', $data);
		$this -> load -> view('footer');
	}
	
	public function search($name = "") {
		if ( !isset( $name ) || $name == "" )
		{
			header("Location: /main");
			return;
		}
		$data = array();
		$this -> load -> model('member');
		$data['user'] = $this -> member -> search_user($name);
		$this -> load -> model('news');
		$data["news"] = $this -> news -> get_entries();
		$data["action"] = "/main/search/" . $name;
		$this -> load -> view('header', $data);
		$this -> load -> view('SearchArtist', $data);
		$this -> load -> view('footer');
	}
	
	public function edit($uid = 1) {
		$data = array();
		$data['curpage_uid'] = $uid;
		if ( isset($_COOKIE['uid']) )
		{
			if ( $data['curpage_uid'] != $_COOKIE['uid'] ) 
			{
				header("Location: /main");
				return;
			}
			$data['uid'] = $_COOKIE['uid'];
		}
		else
		{
			header("Location: /main");
			return;
		}
		$this -> load -> model('member');
		$data['user'] = $this -> member -> get_user($uid);
		if (!isset($data['user'][0])) {
			header("Location: /main");
			return;
		}	
		$this -> load -> model('news');
		$data["news"] = $this -> news -> get_entries();
		
		$data['image'] = $this -> getImageList($data['user'][0] -> file_index);
		$data["action"] = "/main/editProfile";
		$this -> load -> view('header', $data);
		$this -> load -> view('upload_edit');
		$this -> load -> view('footer');
		if (!isset($_COOKIE["file_index"]))
			setcookie("file_index", time(), time() + 3600);
	}
			
	public function user($uid = 1) {
		$data = array();
		$data['curpage_uid'] = $uid;
		if (isset($_COOKIE['uid']))
			$data['uid'] = $_COOKIE['uid'];
		$this -> load -> model('blog');
		$this -> load -> model('member');
		$data['user'] = $this -> member -> get_user($uid);
		if (!isset($data['user'][0])) {
			header("Location: /main");
			return;
		}
		$this -> member -> set_view_count($uid, $data['user'][0] -> view + 1);
		$data['blog'] = $this -> blog -> get_last_entries($uid);
		$data['image'] = $this -> getImageList($data['user'][0] -> file_index);
		$this -> load -> model('news');
		$data["news"] = $this -> news -> get_entries();
		$data["action"] = "/main/add_blog/" . $uid;
		$this -> load -> view('header', $data);
		$this -> load -> view('subpage', $data);
		$this -> load -> view('footer');
	}

	public function create() {
		$data = array();
		if (isset($_COOKIE['uid']))
			$data['uid'] = $_COOKIE['uid'];
		$this -> load -> model('news');
		$data["news"] = $this -> news -> get_entries();
		$data["action"] = "/main/add_member";
		$this -> load -> view('header', $data);
		$this -> load -> view('upload');
		$this -> load -> view('footer');
		if (!isset($_COOKIE["file_index"]))
			setcookie("file_index", time(), time() + 3600);
	}

	public function admin() {
		if (isset($_COOKIE['admin']) && $_COOKIE['admin'] == 1)
		{
			$data = array();
			$this -> load -> model('member');
			$data['user'] = $this -> member -> get_all();
			$this -> load -> view('admin', $data);
			setcookie("admin", 0, time());
		}
		else 
		{
			header("Location: /main");
		}
	}

	public function select() {
		$this -> load -> view('select');
	}
	
	public function selectArtist() {
		$this -> load -> view('selectArtist');
	}

	public function add_blog() {
		$this -> load -> model('blog');
		$this -> blog -> insert_entry();

		$this -> load -> model('member');
		$user = $this -> member -> get_user($_POST["uid"]);
		$message = $this -> lang -> line("header") . $this -> lang -> line("body_blog") . $this -> lang -> line("footer");
		mail($user[0] -> email, $this -> lang -> line("title_blog"), $message);
		header("Location: /main/user/" . $_POST["uid"]);
	}
	
	public function editProfile() {	
		$data = array();
		if (isset($_COOKIE['uid']))
		{
			$data['uid'] = $_COOKIE['uid'];
			$this -> load -> model('member');
			if ($this -> member -> update_entry($data['uid']) == 1) {
				header("Location: /main/user/".$data['uid']);
			}
		}
	}

	public function add_member() {
		$this -> load -> model('member');
		if ($this -> member -> insert_entry() == 1) {
			$this -> lang -> load('email', 'zh-TW');
			$message = $this -> lang -> line("header") . $this -> lang -> line("body_new_ac") . $this -> lang -> line("footer");
			mail($this -> lang -> line("admin_email"), $this -> lang -> line("title_new_ac"), $message);
		}
		session_start();
		$_SESSION["waitingApprove"] = 1;
		header("Location: /main");
	}

	public function approve_member($index, $name) {
		$this -> load -> model('member');
		$this -> member -> approve($index);

		$this -> load -> model('member');
		$user = $this -> member -> get_user_by_index($index);
		$this -> lang -> load('email', 'zh-TW');
		//$message = $this -> lang -> line("header") . $this -> lang -> line("body_approve") . "Password:" . $user[0] -> pw . "\n" . $this -> lang -> line("footer");
		$message = $this -> lang -> line("header") . "User ID:	".$user[0]->email . "\nPassword:	" . $user[0] -> pw . "\n" . $this -> lang -> line("footer");
		mail($user[0] -> email, $this -> lang -> line("title_approve"), $message);

		$this -> load -> model('news');
		$this->news->insert_entry("化妝師 ".urldecode( urldecode($name) )." 加入", $index);
	}

	public function reject_member($index) {
		$this -> load -> model('member');
		$this -> member -> reject($index);
		header("Location: /main/admin");
	}

	public function check_member() {
		$this -> load -> model('member');
		echo $this -> member -> check_ac($_POST["email"]);
	}
	
	public function add_Artist() {
		$data = array();
		
		if (isset($_COOKIE['uid']))
		{
			$data['uid'] = $_COOKIE['uid'];
			$this -> load -> model('member');
			$data['user'] = $this -> member -> get_user($data['uid']);
			if (!isset($data['user'][0])) {
				$this -> load -> view('selectArtist');
				return;
			}
		}
		
		if ( isset ( $data['uid'] ) )
			$tmpFileIdx = $data['user'][0]->file_index;
		else
		{
			if ( isset($_COOKIE['file_index']) )
			{
				$tmpFileIdx = $_COOKIE['file_index'];
			}
		}
		
		if (isset($tmpFileIdx) && isset($_FILES["file1"])) {
			$filename = $_FILES["file1"]["name"][0];
			$image_type = substr($filename, strlen($filename) - 4);
			switch($image_type) {
				case '.jpg':
					break;
				case '.JPG':
					break;
				case '.PNG':
					break;
				case '.png' :
					break;
				case '.GIF':
					break;
				case '.gif' :
					break;
				default :
					echo("Error Invalid Image Type:" . $_FILES["file1"]["type"][0]);
					die ;
					break;
			}

			$dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/thumbnails/' . $tmpFileIdx;
			$path = $this -> getFullUrl() . '/thumbnails/' . $tmpFileIdx;
			if (!is_dir($dir)) {
				mkdir($dir, 0777);
			}
			
			move_uploaded_file($_FILES["file1"]["tmp_name"][0], $dir . '/ArtistPhoto'.$image_type);
			$this -> resizeImage($dir . '/ArtistPhoto'.$image_type, 200);
			
			if (isset($_COOKIE['uid']))
			{
				$this -> member -> set_photo($data['uid'], 'ArtistPhoto'.$image_type);
			}
			else
			{
				setcookie("photo", 'ArtistPhoto'.$image_type, time() + 3600);
			}
			$this -> load -> view('selectArtist');
		}
	}

	public function add_file() {
		$data = array();
		if (isset($_COOKIE['uid']))
		{
			$data['uid'] = $_COOKIE['uid'];
			$this -> load -> model('member');
			$data['user'] = $this -> member -> get_user($data['uid']);
			if (!isset($data['user'][0])) {
				$this -> load -> view('select');
				return;
			}
		}
		
		if ( isset ( $data['uid'] ) )
			$tmpFileIdx = $data['user'][0]->file_index;
		else
			if ( isset($_COOKIE['file_index']) )
				$tmpFileIdx = $_COOKIE['file_index'];
		
		if (isset($tmpFileIdx) && isset($_FILES["file"])) {
			$filename = $_FILES["file"]["name"][0];
			$image_type = substr($filename, strlen($filename) - 4);
			switch($image_type) {
				case '.jpg':
					break;
				case '.JPG':
					break;
				case '.PNG':
					break;
				case '.png' :
					break;
				case '.GIF':
					break;
				case '.gif' :
					break;
				default :
					echo("Error Invalid Image Type:" . $_FILES["file"]["type"][0]);
					die ;
					break;
			}

			$dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/thumbnails/' . $tmpFileIdx;
			$path = $this -> getFullUrl() . '/thumbnails/' . $tmpFileIdx;
			if (!is_dir($dir)) {
				mkdir($dir, 0777);
			}
			
			move_uploaded_file($_FILES["file"]["tmp_name"][0], $dir . '/' . $_FILES["file"]["name"][0]);
			$this -> resizeImage($dir . '/' . $_FILES["file"]["name"][0], 200);
			$this -> load -> view('select');
		}
	}
	
	public function crop_file()
	{
	//	isset($_FILES["file"])
		$this -> load -> view('Crop');
//$file = fopen('myImage.jpg', 'wb');
//fwrite($file, base64_decode($imgData));
	}

	public function login() {
		if ($_POST['email'] == 'admin' && $_POST['pw'] == ADMIN_PASSWORD)
		{
			setcookie("admin", 1, time() + 3600);
			echo '1000';
		}
		else 
		{
			$this -> load -> model('member');
			$result = $this -> member -> login();
			foreach ($result as $row) {
				setcookie("uid", $row -> uid, time() + 3600);
				setcookie("name", $row -> name, time() + 3600);
			}
			echo count($result);
		}
	}

	public function logout() {
		setcookie("uid", '', 1);
		setcookie("name", '', 1);
		header("Location: /main");
	}

	public function get_image_list($file_index) {
		$result = array();
		if ($file_index > 10000) {
			$dir = dir(dirname($_SERVER['SCRIPT_FILENAME']) . '/thumbnails/' . $file_index);
			$path = $this -> getFullUrl() . '/thumbnails/' . $file_index . '/';
			while (false !== ($entry = $dir -> read())) {
				$targetStr = 'ArtistPhoto';
				if (strpos($entry, $targetStr) === false) {
					if (strlen($entry) > 5) {
						array_push($result, $path . $entry);
					}
				}
			}
		}
        header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function get_blog($uid) {
		$this -> load -> model('blog');
		echo json_encode($this -> blog -> get_last_entries($uid));
	}

	public function delete_blog() {
		$this -> load -> model('blog');
		$this -> blog -> delete($_POST['uid'], $_POST['cdate']);
	}

	public function get_news() {
		$this -> load -> model('news');
		echo json_encode($this -> news -> get_entries());
	}

	public function delete_news() {
		$this -> load -> model('news');
		$this -> news -> delete($_POST['id']);
	}

	public function add_news() {
		$this -> load -> model('news');
		$this -> news -> insert_entry($_POST['msg']);
	}

	public function delete_file() {
		$path = dirname($_SERVER['SCRIPT_FILENAME']) . substr($_POST["name"], strlen($this -> getFullUrl()));
		unlink($path);
		echo($path);
	}
	
	public function logoutAdmin()
	{
		setcookie("admin", 0, time());
		header("Location: /main");
	}
	
	public function clear_tmp_file()
	{
		$this -> load -> model('member');
		print_r($this->member->get_all());
		$path = dirname($_SERVER['SCRIPT_FILENAME']) . '/thumbnails/';
		$dir = dir($path);
		while (false !== ($entry = $dir -> read())) 
		{
			$delete = FALSE;
			if (strlen($entry) > 5) 
			{
				$delete = TRUE;
				foreach ($this->member->get_all() as $row) 
				{
					if ($entry == $row->file_index)
					{
						$delete = FALSE;
						break;
					}
				}
			}
			if ($delete)
				$this->rrmdir("$path$entry");
		}
	}

	protected function getFullUrl() {
		return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . (isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : '') . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] . (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] === 443 || $_SERVER['SERVER_PORT'] === 80 ? '' : ':' . $_SERVER['SERVER_PORT']))) . substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
	}

	protected function getImageList($file_index, $one = false) {
		$result = '[';
		if ($file_index > 10000) {
			$dir = dir(dirname($_SERVER['SCRIPT_FILENAME']) . '/thumbnails/' . $file_index);
			$path = $this -> getFullUrl() . '/thumbnails/' . $file_index . '/';
			while (false !== ($entry = $dir -> read())) {
				$targetStr = 'ArtistPhoto';
				if (strpos($entry, $targetStr) === false) {
					if (strlen($entry) > 5) {
						if ($one) {
							return "$result'$path$entry'";
						} else {
							$result = "$result'$path$entry',";
						}
					}
				}
			}
		}
		return trim($result, ',') . ']';
	}

	protected function resizeImage($filename, $newwidth) {
		$image_type = substr($filename, strlen($filename) - 4);
		switch($image_type) {
			case '.jpg' :
			case '.jpeg' :
			case '.JPG' :
				$source = imagecreatefromjpeg($filename);
				break;
			case '.png' :
			case '.PNG' :
				$source = imagecreatefrompng($filename);
				break;
			case '.gif' :
			case '.GIF' :
				$source = imagecreatefromjpeg($filename);
				break;
			default :
				echo("Error Invalid Image Type:".$image_type);
				die ;
				break;
		}
		list($width, $height) = getimagesize($filename);
		$newheight = $height * $newwidth / $width;
		$thumb = imagecreatetruecolor($newwidth, $newheight);
		imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
		imagejpeg($thumb,  $filename,  100);
	}

	protected function getTopMemberList() {
		$result = array();
		$this -> load -> model('member');
		foreach ($this->member->get_top_member() as $row) {
			array_push($result, array($row -> uid, $this -> getImageList($row -> file_index, TRUE)));
		}
		foreach ($this->member->get_new_member() as $row) {
			array_push($result, array($row -> uid, $this -> getImageList($row -> file_index, TRUE)));
		}
		
		return $result;
	}
	
	protected function rrmdir($dir) 
	{
	   foreach(glob($dir . '/*') as $file) 
	   {
	        if(is_dir($file))
	            rrmdir($file);
	        else
	            unlink($file);
	   }
	   rmdir($dir);
	}
	
	public function aboutus() {
		$data = array();
		if (isset($_COOKIE['uid']))
			$data['uid'] = $_COOKIE['uid'];
		$this -> load -> model('news');
		$data["action"] = "/main/login";
		$data["news"] = $this -> news -> get_entries();
		$data["top"] = json_encode($this -> getTopMemberList());
		$this -> load -> view('header', $data);
		$this -> load -> view('about_us');
		$this -> load -> view('footer');
	}
	
	public function reset_pw()
	{
		$this -> load -> model('member');
		if($this -> member -> check_ac($_POST["email"]))
		{
			$pw = $this -> member -> reset_pw();
			$message = "New Password:	" . $pw;
			mail($_POST["email"], "重設密碼成功", $message);
			echo 1;
		}
		else 
		{
			echo 0;
		}
	}
	
	public function set_pw()
	{
		$this -> load -> model('member');
		echo $this -> member -> set_pw();
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
